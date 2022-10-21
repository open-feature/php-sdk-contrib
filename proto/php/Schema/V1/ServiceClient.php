<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Schema\V1;

/**
 * Service defines the exposed rpcs of flagd
 */
class ServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Schema\V1\ResolveBooleanRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResolveBoolean(\Schema\V1\ResolveBooleanRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/schema.v1.Service/ResolveBoolean',
        $argument,
        ['\Schema\V1\ResolveBooleanResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Schema\V1\ResolveStringRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResolveString(\Schema\V1\ResolveStringRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/schema.v1.Service/ResolveString',
        $argument,
        ['\Schema\V1\ResolveStringResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Schema\V1\ResolveFloatRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResolveFloat(\Schema\V1\ResolveFloatRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/schema.v1.Service/ResolveFloat',
        $argument,
        ['\Schema\V1\ResolveFloatResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Schema\V1\ResolveIntRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResolveInt(\Schema\V1\ResolveIntRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/schema.v1.Service/ResolveInt',
        $argument,
        ['\Schema\V1\ResolveIntResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Schema\V1\ResolveObjectRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResolveObject(\Schema\V1\ResolveObjectRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/schema.v1.Service/ResolveObject',
        $argument,
        ['\Schema\V1\ResolveObjectResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Google\Protobuf\GPBEmpty $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function EventStream(\Google\Protobuf\GPBEmpty $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/schema.v1.Service/EventStream',
        $argument,
        ['\Schema\V1\EventStreamResponse', 'decode'],
        $metadata, $options);
    }

}
