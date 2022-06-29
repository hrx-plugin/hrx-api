<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style type="text/css">
        pre {
            border: 1px solid #ccc;
            padding: 5px;
            background: #eee;
        }
    </style>
</head>
<body>
<?php

use HrxApi\API;
use HrxApi\Receiver;
use HrxApi\Shipment;
use HrxApi\Order;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL);

require('../vendor/autoload.php');

$token = ''; // API token required for execution of this file

try {
    $api = new API($token, true, false);

    /*** Pickup locations ***/
    $pickup_locations = $api->getPickupLocations(1, 10);

    /*** Delivery locations ***/
    $delivery_locations = $api->getDeliveryLocations(1, 10);

    /*** Create order ***/
    $receiver = new Receiver();
    $receiver->setName('Tester');
    $receiver->setEmail('test@test.ts');
    $receiver->setPhone('58000000', $delivery_locations[0]['recipient_phone_regexp']); // Without code and checking the value according to the regex specified in delivery location information

    $shipment = new Shipment();
    $shipment->setReference('REF001');
    $shipment->setComment('Comment');
    $shipment->setLength(15); // Dimensions values in cm. Must be between the min and max values specified for the delivery location. If min or max value in delivery location is null, then value not have min/max limit
    $shipment->setWidth(15);
    $shipment->setHeight(15);
    $shipment->setWeight(1); // kg

    $order = new Order();
    $order->setPickupLocationId($pickup_locations[0]['id']);
    $order->setDeliveryLocation($delivery_locations[0]['id']);
    $order->setReceiver($receiver);
    $order->setShipment($shipment);
    $order_data = $order->prepareOrderData();

    $order_response = $api->generateOrder($order_data);

    /*** List orders ***/
    $orders_list = $api->getOrders(1, 10);

    $order_id = isset($orders_list[0]) ? $orders_list[0]['id'] : false;

    /*** Get order by ID ***/
    if ( $order_id ) {
        $order = $api->getOrder($order_id);
    }

    /*** Get order label ***/
    if ( $order_id ) {
        $label = $api->getLabel($order_id);
    }

    /*** List order tracking events ***/
    if ( $order_id ) {
        $tracking_events = $api->getTrackingEvents($order_id);
    }

    /*** Public tracking information (accessible without authorization) ***/
    $tracking_number = ( ! empty($order['tracking_number'])) ? $order['tracking_number'] : 'TRK0099999999';
    $tracking_information = $api->getTrackingInformation($tracking_number);
    
    /*** Echo data ***/
    debug_element('Pickup locations', $pickup_locations);
    debug_element('Delivery locations', $delivery_locations);
    debug_element('Receiver', $receiver);
    debug_element('Shipment', $shipment);
    debug_element('Prepared Order data', $order_data);
    debug_element('Generated Order', $order_response);
    debug_element('Orders list', $orders_list);
    debug_element('Single Order', $order);
    debug_element('Single Order label', $label);
    debug_element('Single Order tracking events', $tracking_events);
    debug_element('Single Order public tracking information', $tracking_information);

} catch (Exception $e) {
    echo '<b>Error:</b> ' . $e->getMessage();
}

function debug_element($title, $element, $print_r = true)
{
    echo $title . ':<br/><pre>';
    echo ($print_r) ? print_r($element, true) : $element;
    echo '</pre>';
    echo '<span>----------------------------------------</span><br/>';
}

?>
</body>
</html>