<?php
/**
 * Update Datadog metrics for a Minecraft server using JSONAPI.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * $this->mcAPI should be a JSONAPI object.
 * https://github.com/alecgorge/jsonapi/blob/master/sdk/php/JSONAPI.php
 * 
 * $this->datadog should be a datadog object from this repository.
 * The constructor accepts an API key as its only argument.
 */
function task_datadog_update()
{
    $apiData = $this->mcAPI->callMultiple( array( 'getOfflinePlayerNames', 'getPlayerCount' ),
                                           array( array(), array() ) );
    $metrics = array();
    $metrics[0] = array();
    $metrics[0]['name'] = 'minecraft.survival.players.total';
    $metrics[0]['value'] = 0;
    foreach( $apiData['success'] as $callFull )
    {
        if( $callFull['result'] != 'success' )
        {
            echo( 'Call to ' . $callFull['source'] . " failed.\n" );
        }
        else
        {
            $call = $callFull['success'];
            switch( $callFull['source'] )
            {
                case 'getOfflinePlayerNames':
                    $metrics[0]['value'] = $metrics[0]['value'] + count( $call );
                    break;
                case 'getPlayerCount':
                    $metrics[0]['value'] = $metrics[0]['value'] + (int) $call;
                    break;
            }
        }
    }
    $this->datadog->addMetrics( $metrics );
}