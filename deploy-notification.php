<?php
/**
 * For information about this script, @see https://github.com/vmattila/codebase-deploy-ticket-notification
 */
 
// Reading configuration from a separate file
include 'configuration.inc.php';

// Reading POST'ed payload information and feeding it to associative array
$json_data = $_POST['payload'];
$json = json_decode($json_data, true);
if (!$json) {
    exit;
}

// Start revision
$start_rev = $json['start_revision']['ref'];
// End revision
$end_rev = $json['end_revision']['ref'];
// Building the deployment URL
$deploy_url = $deployhq_url_base . $json['identifier'];
// Server where the deployment has been done
$server = $json['servers'][0]['name'];

// If this server should be skipped, doing it right here
if (in_array($server, $skip_server)) { exit; }

// Updating local repository proxy from the Codebase HQ, getting the deployed revision log
$output = array();
$get_repo_log_cmd = "cd " . escapeshellarg($local_repository_path) . " && git fetch " . escapeshellarg($codebase_remote_name) . " && git log --pretty=format:%B " . escapeshellarg($start_rev) . ".." . escapeshellarg($end_rev) . "'";
if ($sudo_to_user) {
    $cmd = "sudo -u " . escapeshellarg($sudo_to_user) . " -- sh -c " . escapeshellarg($get_repo_log_cmd);
} else {
    $cmd = $get_repo_log_cmd;
}
$commits = exec($cmd, $output);
$log = join("\n", $output);

// Fetching all completed tickets from the git log. Notation [completed:XXX] where XXX is the ticket id 
preg_match_all("/completed:([0-9]+)/i", $log, $matches, PREG_SET_ORDER);
foreach ($matches as $match) {
    $ticket_id = $match[1];

    // Here we build the message how the ticket is updated.
    $placeholders = array(
        '{server}' => $server,
        '{deployment_url}' => $deployment_url,
        );
    $msg = str_replace(array_keys($placeholders), array_values($placeholders), $ticket_comment_template);

    // If we have defined that the ticket should a new status, the status ID is given as $update_status
    if ($update_status) {
        $changes_xml = "<changes><status-id>" . $update_status . "</status-id></changes>";
    } else {
        $changes_xml = "";
    }

    // Building XML for the update
    $xml = "<ticket-note><content>" . htmlspecialchars($msg) . "</content>" . $changes_xml . "</ticket-note>";
    $ticket_api_url = "https://api3.codebasehq.com/e/tickets/" . $ticket_id . "/notes";
    
    // Posting the ticket update to Codebase API
    $cmd = "/usr/bin/curl -X POST -d " . escapeshellarg($xml) . " --user " . escapeshellarg($codebase_user) . " -H 'Accept: application/xml' -H 'Content-Type: application/xml' " .  . escapeshellarg($ticket_api_url);
    $output = array();
    exec($cmd, $output);
}

exit;