<?php

use PHPUnit\Framework\TestCase;

use \Scomrie\Baserow\Baserow;

class BasicTest extends TestCase
{
    private $blankApiKey   = "API key cannot be blank";
    private $noClassConfig = "__construct() - Configuration data is missing";

    public function testCreateClass()
    {
        try {
            $baserow = new Baserow();
            $this->fail( "Exception should be thrown" );
        } catch (Exception $e) {
            $this->assertEquals( $this->noClassConfig, $e->getMessage());
        }

        try {
            $baserow = new Baserow([]);
            $this->fail( "Exception should be thrown" );
        } catch (Exception $e) {
            $this->assertEquals( $this->blankApiKey, $e->getMessage());
        }
    }

    public function testGetSetApiKey()
    {
        $baserow = new Baserow(['api_key' => 'fakeApiKey']);
        $this->assertEquals($baserow->getKey(), 'fakeApiKey');

        $baserow->setKey( 123456 );
        $this->assertEquals($baserow->getKey(), '123456' );

        try {
            $baserow->setKey();
            $this->fail( "Exception should be thrown" );
        } catch (Exception $e) {
            $this->assertEquals( $this->blankApiKey, $e->getMessage());
        }

        try {
            $baserow->setKey("");
            $this->fail( "Exception should be thrown" );
        } catch (Exception $e) {
            $this->assertEquals( $this->blankApiKey, $e->getMessage());
        }
    }
}
