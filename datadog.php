<?php
/**
 * Enables sending metrics and events to Datadog.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * @package MineCMS
 * @name datadog
 * @version 1.0
 * @author Nick <nick@stormjive.com>
 * @copyright 2013 Stormjive Interactive
 * @license Simple Public License (SimPL-2.0) http://opensource.org/licenses/Simple-2.0
 * @link http://gameon365.net
 * @location X:\Code\MineCMS\admin\applications_addon\other\minecms\sources\classes\datadog.php
 */

class datadog
{
    /**
     * API Endpoint (including version)
     * 
     * @constant string
     */
    const API_ENDPOINT = 'https://app.datadoghq.com/api/v1/';
    
    /**
     * User Agent for API Requests
     * 
     * @constant string
     */
    const CLIENT_USER_AGENT = 'libcurl Simpledog/rev-current +http://github.com/Stormjive/Simpledog';
    
    /**
     * API Key
     * 
     * @access private
     * @var string
     */
    private $_apiKey;
    
    /**
     * Enable non-fatal error reporting?
     * 
     * @access public
     * @var boolean
     */
    public $errorReporting;
    
    /**
     * Constructor
     * 
     * @access public
     * @param string $apiLey
     * @return void
     */
    public function __construct( $apiKey )
    {
        if( !function_exists( 'json_encode' ) )
        {
            /**
             * @error_id 2
             * @severity Fatal -> Missing Function
             * @explanation JSON functions are not present in the PHP compilation.
             */
            throw new Exception( '[#2] ' . __CLASS__ . ' requires JSON functions.', 2 );
        }
        if( !function_exists( 'curl_init' ) )
        {
            /**
             * @error_id 3
             * @severity Fatal -> Missing Functions
             * @explanation CURL functions are not present in the PHP compilation.
             */
            throw new Exception( '[#3] ' . __CLASS__ . ' requires CURL functions.', 3 );
        }
        
        $this->_apiKey = $apiKey;
        $this->errorReporting = TRUE;
    }
    
    /**
     * Fetch data from the API.
     * 
     * @access protected
     * @param string $link
     * @param string $post
     * @return string
     * @todo Error response handling (?)
     * @todo Switch to HttpRequestPool
     * @todo Generate client agent string using site URL
     */
    protected function _fetchData( $link, $post )
    {
        $ch = curl_init();
        
        curl_setopt( $ch, CURLOPT_URL, $link . '?api_key=' . $this->_apiKey );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 6 );
        curl_setopt( $ch, CURLOPT_ENCODING, '' );
        curl_setopt( $ch, CURLOPT_USERAGENT, self::CLIENT_USER_AGENT );
        curl_setopt( $ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, TRUE );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt( $ch, CURLOPT_POST, TRUE );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
        
        $data = curl_exec( $ch );
        $error = curl_errno( $ch );        
        curl_close( $ch );
        
        if( $error )
        {
            /**
             * @error_id 7
             * @severity Execution Halt -> No Return Data
             * @explanation There was an error executing the API call.
             */
            if( $this->errorReporting )
            {
                trigger_error( '[#7] An error ocurred while attempting to execute the API call.', E_USER_NOTICE );
            }
            return FALSE;
        }
        return $data;
    }
    
    /**
     * Send data point(s) for metric(s) to Datadog.
     * 
     * @access public
     * @param array $metrics
     * @param boolean $echo
     * @throws Exception 
     */
    public function addMetrics( $metrics, $echo = FALSE )
    {
        /**
         * @error_id 40
         * @severity Execution Halt -> Improper Argument
         * @explanation The call to addMetrics should only hvae a single argument, an array. 
         */
        if( ! is_array( $metrics ) )
        {
            throw new Exception( '[#40] Argument $metrics must be of type array.', 40 );
        }
        
        $postDataArray = array();
        $postDataArray['series'] = array();
        foreach( $metrics as $metric )
        {
            $temp = array();
            $temp['metric'] = $metric['name'];
            $temp['points'] = array( array( time(), $metric['value'] ) );
            if( isset( $metric['type'] ) )
            {
                $temp['type'] = $metric['type'];
            }
            if( isset( $metric['host'] ) )
            {
                $temp['host'] = $metric['host'];
            }
            if( isset( $metric['tags'] ) )
            {
                if( is_array( $metric['tags'] ) )
                {
                    $temp['tags'] = $metric['tags'];
                }
                else
                {
                    throw new Exception( '[#41] Argument $metrics[][\'tags\'] must be of type array.', 41 );
                }
            }
            $postDataArray['series'][] = $temp;
            
            // Don't do this in your code, kids.
            if( $echo )
            {
                echo( 'Sending metric ' . $metric['name'] . ' with value ' . $metric['value'] . ".\n" );
            }
        }
        $postData = json_encode( $postDataArray );
        $this->_fetchData( self::API_ENDPOINT . 'series' , $postData );
    }
}