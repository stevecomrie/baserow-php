<?php

namespace Scomrie\Baserow;

class Response
{

    /**
     * @var Baserow Instance of Baserow
     */
    private $baserow;

    /**
     * @var Request Instance or Request
     */
    private $request;

    /**
     * @var string Response content
     */
    private $content = "";

    /**
     * @var bool|\stdClass Response
     */
    private $parsedContent = false;

    /**
     * Response constructor.
     * @param Baserow $baserow Instance of Baserow
     * @param Request $request Instance of Request
     * @param string $content Content string
     */
    public function __construct( $baserow, $request, $content )
    {

        $this->baserow = $baserow;

        $this->request = $request;

        $this->content = $content;

        try
        {
            $this->parsedContent = json_decode( $content );
        }
        catch ( \Exception $e )
        {
            $this->parsedContent = false;
        }
    }

    public function __get( $key )
    {
        if( ! $this->parsedContent || ! isset( $this->parsedContent->$key ) )
        {
            return null;
        }

        return $this->parsedContent->$key;
    }

    public function __toString()
    {
        return $this->content;
    }

    public function __isset( $key )
    {
        return $this->parsedContent && isset( $this->parsedContent->$key );
    }

    public function parsedContent() {
        return $this->parsedContent;
    }
}