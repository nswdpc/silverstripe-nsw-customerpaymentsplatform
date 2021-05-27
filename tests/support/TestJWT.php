<?php

namespace NSWDPC\Payments\NSWGOVCPP\Tests;

use SilverStripe\Core\Config\Configurable;
use Symfony\Component\Yaml\Parser;

/**
 * Represents a configurable set of values to use for a JWT test
 */
class TestJWT {

    use Configurable;

    /**
     * @var mixed
     */
    private $jwtData = null;

    /**
     * @return mixed
     */
    public function getVal($key) {
        if(!$this->jwtData) {
            $parser = new Parser();
            $result = $parser->parse(file_get_contents( dirname(__FILE__) . '/data/jwt.yml' ) );
            if(!is_array($result)) {
                throw new \Exception("Invalid result frpm jwt.yml parsing");
            }
            $this->jwtData = $result;
        }
        if(isset($this->jwtData[$key])) {
            return $this->jwtData[$key];
        }
        throw new \Exception("Key {$key} not found in yml test data");
    }

}
