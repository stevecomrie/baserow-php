<?php

namespace Scomrie\Baserow;

class Request
{

    /**
     * @var Baserow Instance of Baserow
     */
    private $baserow;
    /**
     * @var resource Instance of CURL
     */
    private $curl;
    /**
     * @var string Content type
     */
    private $content_type;
    /**
     * @var array Request data
     */
    private $data = [];
    /**
     * @var bool Is it a POST request?
     */
    private $is_post = false;


    private $response;

    /**
     * Create a Request to Baserow API
     * @param Baserow $baserow Instance of Baserow
     * @param string $content_type Content type
     * @param array $data Request data
     * @param bool|string $is_post Is it a POST request?
     */
    public function __construct( $baserow, $content_type, $data = [], $is_post = false )
    {

        $this->baserow = $baserow;
        $this->content_type = $content_type;
        $this->data = $data;
        $this->is_post = $is_post;

    }

    private function init()
    {
        $headers = array(
            'Content-Type: application/json',
            sprintf('Authorization: Token %s', $this->baserow->getKey())
        );

        $request = $this->content_type;

        $request_parts = explode( '/', $request );

        $request_parts = array_map( 'rawurlencode', $request_parts );

        $request = join( '/', $request_parts ) . '/';

        $url_encoded = false;

        if( ! $this->is_post || strtolower( $this->is_post ) === 'delete' )
        {
            if (!empty($this->data)){
                $data = http_build_query($this->data);
                $request .= "?" . $data;
            }

            $url_encoded = true;
        }

        $curl = curl_init($this->baserow->getApiUrl($request));

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if( $this->is_post )
        {
            $postData = [];
            $postType = strtoupper( $this->is_post );

            curl_setopt($curl,CURLOPT_POST, true);

            if( in_array( $postType, ['PATCH', 'DELETE'] ) ) {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $postType );
            }

            // POST & PATCH send a payload that needs to be modified
            if( $postType != 'DELETE' )
            {
                // make sure any straight integer values in the data array
                // are reformatted using (int) which prevents json_encode() from
                // wrapping them in quotes, since baserow interprets integers
                // wrapped in quotes as strings, which breaks updating select
                // fields by option id. this is a preferable method to using
                // JSON_NUMERIC_CHECK as there are some legacy issues with the
                // way that flag tests and validates numbers
                $postData = $this->data;
                foreach( $postData AS $key => $value ) {
                    if( is_numeric($value) && preg_match("/^\d+$/", $value ) ) {
                        $postData[$key] = (int) $value;
                    }

                    if( is_array($value) && !empty($value) ) {
                        $cleanValues = [];
                        foreach( $value AS $arrayVal ) {
                            $cleanValues[] =
                                ( is_numeric($arrayVal) && preg_match("/^\d+$/", $arrayVal ) )
                                ? (int) $arrayVal
                                : $arrayVal;
                        }

                        $postData[$key] = empty($cleanValues) ? $value : $cleanValues;
                    }
                }
            }

            curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($postData));
        }

        $this->curl = $curl;
    }

    /**
     * @return Response Get response from API
     */
    public function getResponse()
    {
        $this->init();

        $response_string = curl_exec( $this->curl );

        $this->response = new Response( $this->baserow, $this, $response_string );

        return $this->response;
    }

    public function __set( $key, $value )
    {
        if( ! is_array( $this->data ) )
        {
            $this->data = [];
        }

        $this->data[ $key ] = $value;
    }
}