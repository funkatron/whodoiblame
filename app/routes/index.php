<?php
use \BlameApp\Lib\GeocodioApi;
use \BlameApp\Lib\GovtrackApi;

$app->get('/', function () use ($app) {
    $app->render('pages/index.html');
});

$app->post('/', function () use ($app) {

    // should sanitize this
    $address = $app->request->post('address');

    $geo = new GeocodioApi($app);
    $rs = $geo->get('geocode', [
                "q" => $address,
                "fields" => "cd,stateleg"
            ]);
    if ($rs['status'] !== 200) {
        $app->halt(500, "oh crap");
    }

    $results = $rs['body']['results'];
    if ($results[0]) {
        $district = 0;
        $state = $results[0]['address_components']['state'];
        if (!empty($results[0]['fields']['congressional_district'])) {
            $district = $results[0]['fields']['congressional_district']['district_number'];
        }

        $gov = new GovtrackApi($app);
        $rs = $gov->get('role', [
                        "state" => $state,
                        "current" => true,
                    ]);
        if ($rs['status'] !== 200) {
            $app->halt(500, "oh crap");
        }

        $reps = $rs['body']['objects'];

        $matching_reps = [];
        foreach ($reps as $key => $rep) {
            if ($district === $rep['district']) {
                $matching_reps[] = $rep;
            } else if ($rep['role_type'] === 'senator') {
                $matching_reps[] = $rep;
            }
        }

        $app->render('pages/index.html', [
                     'address' => $address,
                     'reps' => $matching_reps
                     ]);
    }
});
