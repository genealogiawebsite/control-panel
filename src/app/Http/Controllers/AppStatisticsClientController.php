<?php

namespace LaravelEnso\AppStatisticsClient\app\Http\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LaravelEnso\AppStatisticsClient\app\Classes\ResponseDataWrapper;
use LaravelEnso\AppStatisticsClient\app\Classes\StatisticsRequestHub;
use LaravelEnso\AppStatisticsClient\app\Classes\TokenRequestHub;
use LaravelEnso\AppStatisticsClient\app\Enums\DataTypesEnum;
use LaravelEnso\AppStatisticsClient\app\Enums\SubscribedAppTypesEnum;
use LaravelEnso\AppStatisticsClient\app\Models\SubscribedApp;
use LaravelEnso\Core\app\Exceptions\EnsoException;

class AppStatisticsClientController extends Controller
{
    public function index()
    {
        $activeApps = json_encode(SubscribedApp::all());
        $subscribedAppTypes = (new SubscribedAppTypesEnum())->getJsonKVData();
        $dataTypes = json_encode((new DataTypesEnum())->getKeys());

        return view('laravel-enso/app-statistics-client::appStatisticsClient.index',
            compact('activeApps', 'subscribedAppTypes', 'dataTypes'));
    }

    public function store(Request $request)
    {
        $tokenResponseData = TokenRequestHub::requestNewToken($request);

        if (!$tokenResponseData) {
            throw new EnsoException(__('Unable to get token. Check data!'));
        }

        try {
            $newSubscribedApp = null;

            DB::transaction(function () use ($request, $tokenResponseData, &$newSubscribedApp) {
                $newSubscribedApp = new SubscribedApp($request->all());
                $newSubscribedApp->token = $tokenResponseData->access_token;
                $newSubscribedApp->save();
            });

            return $newSubscribedApp;
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
            $this->deleteToken($tokenResponseData->id);
        }
    }

    public function getAll(Request $request, SubscribedApp $subscribedApp)
    {
        $result = new ResponseDataWrapper($subscribedApp->id, $subscribedApp->name, $subscribedApp->type);

        try {
            $response = StatisticsRequestHub::getAll($request, $subscribedApp);
            $result->data = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $result->addError($e->getMessage());
        }

        return $result;
    }

    public function getConsolidated(Request $request)
    {
        $activeApps = SubscribedApp::all();

        $result = [];

        foreach ($activeApps as $app) {
            $result[] = $this->getAll($request, $app);
        }

        return $result;
    }

    private function deleteToken($id)
    {

        //not supported by laravel

        /*
        $client = new Client();

        $headers = [
            'Accept'=>'application/json'
        ];

        $res = $client->request('DELETE', 'http://enso.dev/oauth/tokens/' . $id);

        $responseStatusCode = $res->getStatusCode();
        if($responseStatusCode !== 200) {
            throw new EnsoException(__('Could not delete token'));
        }
        */
    }

    public function clearLaravelLog(Request $request, SubscribedApp $subscribedApp)
    {
        $response = StatisticsRequestHub::clearLaravelLog($request, $subscribedApp);

        return $response->getStatusCode();
    }
}
