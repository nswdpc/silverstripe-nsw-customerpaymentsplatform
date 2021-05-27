<?php

namespace NSWDPC\Payments\NSWGOVCPP\Tests;

use Firebase\JWT\JWT;

/**
 * Common JWT handling for unit + functional tests
 */
trait TestJWTHandling {

    public function getValidJWT(TestJWT $testJWT) : string {

        // validate test payload
        $payload = json_decode(trim($testJWT->getVal('jwtPayload')), false, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($payload);

        // bump expiry time into the future
        $payload->exp = time() + 300;

        // encode the payload to create a JWT
        $jwt = JWT::encode(
            $payload,
            $testJWT->getVal('jwtPrivateKey'),
            $testJWT->getVal('jwtAlgo'),
        );

        // check it matches expected JWT
        $this->assertNotEmpty($jwt, "JWT is empty");

        // test JWT decode
        $result = JWT::decode(
            $jwt,
            $testJWT->getVal('jwtPublicKey'),
            $testJWT->getVal('jwtAlgos')
        );

        // JWT should not match as exp changed
        $this->assertNotEquals(
            $testJWT->getVal('expectedJwt'),
            $jwt
        );

        // assert payload matches
        $this->assertEquals(
            $payload,
            $result
        );

        // return the valid jwt
        return $jwt;

    }
}
