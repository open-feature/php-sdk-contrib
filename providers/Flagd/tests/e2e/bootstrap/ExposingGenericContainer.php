<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\Test\e2e\bootstrap;

use Docker\API\Model\ContainerConfigExposedPortsItem;
use Docker\API\Model\ContainersCreatePostBody;
use Testcontainers\Container\GenericContainer;

/**
 * GenericContainer that also declares the exposed ports in the container's `Config.ExposedPorts`.
 *
 * testcontainers-php only sets `HostConfig.PortBindings`, not `Config.ExposedPorts`. For images
 * that do not `EXPOSE` their ports (the flagd testbed image does not), some Docker daemons then
 * report an empty `NetworkSettings.Ports`, so `getMappedPort()` cannot resolve the host port. This
 * is observed on GitHub Actions runners while local Docker is lenient. Declaring the ports fixes it.
 */
final class ExposingGenericContainer extends GenericContainer
{
    protected function createContainerConfig(): ContainersCreatePostBody
    {
        $config = parent::createContainerConfig();

        $exposed = [];
        foreach ($this->exposedPorts as $port) {
            $item = new ContainerConfigExposedPortsItem();
            $item['exposed'] = true;
            $exposed[$port] = $item;
        }

        if ($exposed !== []) {
            $config->setExposedPorts($exposed);
        }

        return $config;
    }
}
