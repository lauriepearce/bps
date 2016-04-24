<?php

/* Include Files *********************/
require_once("../../libs/env.php");
/*************************************/

$opts = array('http' =>
    array(
        'method'  => 'GET',
	'header'  => "Content-type: application/xml\n"
                   . "Accept: application/json",
    )
);

$context  = stream_context_create($opts);


$jjson = file_get_contents($CFG->serverwwwroot.$CFG->svcsbase."/workspaces/1/graph", false, $context);


$json = json_decode($jjson);

$output_json = array(
	'directed' => true,
	'graph' => array(),
	'nodes' => array(),
	'links' => array(),
	'multigraph' => false,
);

$internal_ids = array();

foreach ($json->graph->node as $node) {
	$output_node = array(
		'id' => (int) $node->{'@id'},
	);
	foreach ($node->data as $node_data) {
		$output_node[$node_data->{'@key'}] = $node_data->{'$'};
		if ($node_data->{'@key'} == 'GMLid') {
			$internal_ids[$node_data->{'$'}] = (int) $node->{'@id'};
		}
	}
	$output_json['nodes'][] = $output_node;
}

foreach ($json->graph->edge as $edge) {
	$output_edge = array(
		'source' => $internal_ids[$edge->{'@source'}],
		'target' => $internal_ids[$edge->{'@target'}],
		'directed' => $edge->{'@directed'},
	);
	foreach ($edge->data as $edge_data) {
		$output_edge[$edge_data->{'@key'}] = $edge_data->{'$'};
	}
	$output_json['links'][] = $output_edge;
}

print json_encode($output_json);

?>