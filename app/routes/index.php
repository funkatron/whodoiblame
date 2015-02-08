<?php
use \BlameApp\Lib\GeocodioApi;
use \BlameApp\Lib\GovtrackApi;
use \BlameApp\Lib\Cache;


$app->error(function (\Exception $e) use ($app) {
    $app->log->addError("Exception thrown: " . $e->getMessage());
    $app->render('pages/error.html', 500);
});

$app->get('/', function () use ($app) {
    $app->render('pages/index.html');
});


$app->get('/:state/:district', function($state, $district) use ($app) {

    $cache = new Cache($app);

    $gov_cache_key = "GOV_{$state}_{$district}";
    $matching_reps = $cache->get($gov_cache_key);
    $region_description = "{$state} Congressional District {$district}";
    if (!$matching_reps) {
        $app->log->addDebug("Retrieving {$gov_cache_key} from API");
        $gov = new GovtrackApi($app);
        $rs = $gov->get('role', [
                        "state" => $state,
                        "current" => 'true',
                    ]);
        if ($rs['status'] !== 200) {
            $app->render('pages/error.html', ['error_msg' => 'Oh crap.'], 500);
            return;
        }

        $reps = $rs['body']['objects'];

        foreach ($reps as $key => $rep) {
            $app->log->addDebug("{$rep['district']}, {$district}");
            if ((int)$district === (int)$rep['district']) {
                $matching_reps[] = $rep;
            } else if ($rep['role_type'] === 'senator') {
                $matching_reps[] = $rep;
            }
        }
        $cache->set($gov_cache_key, $matching_reps);
    }



    $app->render('pages/index.html', [
                 'region_description' => $region_description,
                 'reps' => $matching_reps,
                 ]);

})->conditions(array('state' => '[A-Z]{2}', 'district' => '\d{1,4}'));



$app->post('/', function () use ($app) {

    $cache = new Cache($app);

    // should sanitize this
    $address = $app->request->post('address');

    $geo_cache_key = 'GEO_' . md5($address);

    $results = $cache->get($geo_cache_key);
    if (!$results) {
        $app->log->addDebug("Retrieving {$geo_cache_key} from API");
        $geo = new GeocodioApi($app);
        $rs = $geo->get('geocode', [
                    "q" => $address,
                    "fields" => "cd,stateleg"
                ]);
        if ($rs['status'] !== 200) {
            $app->render('pages/error.html', ['error_msg' => 'Oh crap.'], 500);
            return;
        }

        $results = $rs['body']['results'];
    }

    if (!empty($results[0])) {
        // cache the results if as expected
        $cache->set($geo_cache_key, $results);
        $district = 0;
        $state = $results[0]['address_components']['state'];
        if (!empty($results[0]['fields']['congressional_district'])) {
            $district = $results[0]['fields']['congressional_district']['district_number'];
        }

        $app->redirect("/{$state}/$district", 301);
    }

    $app->render('pages/error.html', ['error_msg' => 'The response from Geocod.io was not as expected. Maybe that was an invalid US address or ZIP.'], 500);
    return;
});

