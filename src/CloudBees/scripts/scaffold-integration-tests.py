#!/usr/bin/env python

from http.client import HTTPConnection
import json
import os
import subprocess
import sys
import time


SEED_DATA = [
  {
    "name": "dev.openfeature.bool_flag",
    "expression": "true"
  },
  {
    "name": "dev.openfeature.string_flag",
    "expression": "\"string-value\""
  },
  {
    "name": "dev.openfeature.int_flag",
    "expression": "42"
  },
  {
    "name": "dev.openfeature.float_flag",
    "expression": "3.14"
  },
  {
    "name": "dev.openfeature.object_flag",
    # TODO: Implement objects, blocked by https://github.com/rollout/rox-php/issues/37
    # "expression": "\"{\"name\":\"OpenFeature\",\"version\":\"1.0.0\"}\"",
    "expression": "\"{}\""
  }
]


class HTTPMethod:
  GET = 'GET'
  POST = 'POST'
  PUT = 'PUT'
  PATCH = 'PATCH'
  DELETE = 'DELETE'

  __DATA_METHOD__ = []
  __initialized__ = False

  def is_data_method(method):
    if (not HTTPMethod.__initialized__):
      HTTPMethod.__DATA_METHOD__ = set([HTTPMethod.POST, HTTPMethod.PUT, HTTPMethod.PATCH])

    return method in HTTPMethod.__DATA_METHOD__

class Config:
  def __init__(self, port=4444):
    self.port = port

  def get_port(self):
    return self.port

class SerializedResponse:
  def __init__(self, status, data=None):
    self.status = status
    self.data = data

  def get_status(self):
    return self.status
  
  def get_data(self):
    return self.data

  def is_success(self):
    return not self.is_error()

  def is_error(self):
    return self.status >= 400


class RoxyService:
  def __init__(self, container_name, config):
    self.container_name = container_name
    self.config = config
  
  def setup(self):
    subprocess.run([
      "docker", "run",
        "--rm",
        "--name", self.container_name,
        "-p", f"{self.config.get_port()}:3333",
        "-d",
          "rollout/roxy:latest"
    ])

  def teardown(self):
    subprocess.run(["docker", "stop", self.container_name])

class RoxyClient:
  def __init__(self, hostname, port):
    self.hostname = hostname
    self.port = port
    self.__default_data_headers__ = {
      'Content-Type': 'application/json'
    }
  
  def init(self):
    client = HTTPConnection(self.hostname, self.port)
    self.client = client

  def is_ready(self):
    return bool(self.client)

  def get_all_flags(self):
    return self.__send__(HTTPMethod.GET, "/")

  def get_flag(self, name):
    return self.__send__(HTTPMethod.GET, f"/{name}")

  def delete_all_flags(self):
    return self.__send__(HTTPMethod.DELETE, "/")

  def delete_flag(self, name):
    return self.__send__(HTTPMethod.DELETE, f"/{name}")

  def update_flag(self, name, expression=None):
    return self.__send__(HTTPMethod.POST, f"/{name}", json.dumps({'expression': expression}))

  def __send__(self, method, path, data=None, headers=dict(), skip_response=False):
    self.init()

    client = self.client
    path = f"/flags{path}"

    headers = {**headers, **self.__default_data_headers__}
    
    client.request(method, path, data, headers)

    if (skip_response):
      return None

    try:
      res = client.getresponse()
      if (res.status >= 400):
        return SerializedResponse(res.status)

      raw_data = res.read()
      data = json.loads(raw_data)

      return SerializedResponse(res.status, data)
    except Exception:
      return SerializedResponse(418)


class RoxyHealthcheck:
  def __init__(self, client, max_attempts=10):
    self.client = client
    self.max_attempts = max_attempts

  def await_healthy(self):
    tries = 0
    while (not self.healthcheck()):
      tries = tries + 1
      time.sleep(1)

      if (tries >= self.max_attempts):
        return False
    
    return True
  
  def healthcheck(self):
    return self.client.get_all_flags().is_success()

class LogLevel:
  TRACE = 0
  DEBUG = 100
  INFO = 200
  WARN = 300
  ERROR = 400
  ALERT = 500

class Logger:
  def __init__(self, log_level):
    self.log_level = self.__determine_log_level__(log_level)

  def trace(self, *args):
    print(*args)

  def debug(self, *args):
    print(*args)

  def info(self, *args):
    print(*args)

  def warn(self, *args):
    print(*args)

  def error(self, *args):
    print(*args)

  def alert(self, *args):
    print(*args)

  def __determine_log_level__(self, log_level=None):
    if (isinstance(log_level, int)):
      return log_level

    if (isinstance(log_level, str)):
      normalized_log_level = log_level.lower()
      if (normalized_log_level == 'trace'):
        return LogLevel.TRACE
      if (normalized_log_level == 'debug'):
        return LogLevel.DEBUG
      if (normalized_log_level == 'info'):
        return LogLevel.INFO
      if (normalized_log_level == 'warn'):
        return LogLevel.WARN
      if (normalized_log_level == 'error'):
        return LogLevel.ERROR
      if (normalized_log_level == 'alert'):
        return LogLevel.ALERT
    
    return LogLevel.INFO

def coalesce(*arg):
  return reduce(lambda x, y: x if x is not None else y, arg)

def env(key, default_value):
  return os.environ.get(key, default_value)

def main():
  log_level = env('LOG_LEVEL', 'info')
  logger = Logger(log_level)

  host = env('HOST', 'localhost')
  port = env('PORT', 4444)
  image_name = env('IMAGE_NAME', 'rollout/roxy:latest')
  container_name = env('CONTAINER_NAME', 'roxy-integration-test-server')

  config = Config(port)
  client = RoxyClient(host, port)
  healthcheck = RoxyHealthcheck(client)
  service = RoxyService(container_name, config)

  if (len(sys.argv) > 1):
    # subcommands
    subcommand = sys.argv[1]
    if (subcommand == 'stop'):
      service.teardown()
      return

  # main command
  logger.info("Tearing down existing services...")
  service.teardown()

  logger.info("Setting up Roxy server...")
  service.setup()

  logger.info("Awaiting healthy server...")
  healthcheck.await_healthy()

  logger.info("Deleting any existing flags...")
  client.delete_all_flags()

  logger.info("Seeding data...")

  for seed in SEED_DATA:
    client.update_flag(seed['name'], seed['expression'])

  logger.info("Seeding complete...")

  res = client.get_all_flags()

  logger.debug(json.dumps(res.get_data()))

  res = client.get_flag('dev.openfeature.object_flag')

  logger.debug(res.get_status())
  logger.debug(json.dumps(res.get_data()))
  
  logger.info("Successfully set up integration test seed data")

if __name__ == '__main__':
  main()