<?php

namespace lopez_i\UrlDatabaseSigner;

use lopez_i\UrlDatabaseSigner\Jobs\RemoveExpiredSignedUrl;
use lopez_i\UrlDatabaseSigner\Model\SignedUrls;
use ArrayIterator;
use Carbon\Carbon;
use Crypt;
use Illuminate\Http\Request;
use League\Uri\Components\Query;
use League\Uri\Schemes\Http as HttpUri;
use Ramsey\Uuid\Uuid;

/**
 * Class UrlSigner
 * @package App\Http\Controllers\Library
 */
class UrlDatabaseSigner
{
    /**
     * @var string
     */
    private static $signature_raw_value = "url_signature";

    /**
     * @var string
     */
    private static $request_type_raw_value = 'request_type';

    /**
     * @var string
     */
    private static $expire_at_raw_value = "expire_at";


    /**
     * @param string $url
     * @param string $request_type
     * @param string $user_id
     * @param int $expiration_time
     *
     * @return string
     */
    public static function create(string $url, string $request_type, string $user_id, int $expiration_time = 5): string
    {
        $urlAlreadyCreated = self::redirectIfUrlAlreadyExists($url, $request_type, $user_id);
        if ($urlAlreadyCreated != null)
            return $urlAlreadyCreated;

        $url = HttpUri::createFromString($url);

        $url_signature = Uuid::uuid5(Uuid::NAMESPACE_DNS, $url)->toString();
        $request_at = Carbon::now();
        $expire_at = Carbon::now()->addMinute($expiration_time);
        self::createRequest($url_signature, $user_id, $request_type, $request_at->timestamp, $expire_at->timestamp);

        $job = new RemoveExpiredSignedUrl($user_id, $request_type);
        $job->delay(Carbon::now()->addMinute($expiration_time - 1)->addSeconds(50));
        dispatch($job);
        \Log::debug('LOG HAVE BEEN DISPATCHED');

        return self::buildUrlWithQuery($url, $url_signature,
            $expire_at->timestamp, $request_type)->__toString();
    }

    /**
     * @param string $url
     * @param string $request_type
     * @param string $user_id
     * @return string
     */
    public static function redirectIfUrlAlreadyExists(string $url, string $request_type, string $user_id)
    {
        $url = HttpUri::createFromString($url);
        $userUrls = SignedUrls::where('user_id', $user_id)->get();

        foreach ($userUrls as $user_url)
        {
            if ($user_url && $user_url->request_type == $request_type)
                return self::buildUrlWithQuery($url, $user_url->url_signature,
                    $user_url->expire_at, $request_type)->__toString();
        }

        return null;
    }

    /**
     * @param HttpUri $url
     * @param string $url_signature
     * @param int $expire_at
     * @param string $request_type
     *
     * @return \League\Uri\Schemes\AbstractUri|static
     */
    private static function buildUrlWithQuery(HttpUri $url, string $url_signature, int $expire_at, string $request_type)
    {
        $url_signature_key = Crypt::encrypt(self::$signature_raw_value);
        $expire_at_key = Crypt::encrypt(self::$expire_at_raw_value);
        $request_type = Crypt::encrypt($request_type);

        $query = Query::createFromPairs(
            [
                $url_signature_key => $url_signature,
                $expire_at_key => $expire_at,
                Crypt::encrypt(self::$request_type_raw_value) => $request_type
            ]);

        return $url->withQuery($query->__toString());
    }

    /**
     * @param string $url_signature
     * @param string $user_id
     * @param string $request_type
     * @param int $requested_at
     * @param int $expire_at
     * @param        void
     */
    private static function createRequest(string $url_signature, string $user_id, string $request_type,
                                          int $requested_at, int $expire_at)
    {
        SignedUrls::create(
            [
                'url_signature' => $url_signature,
                'user_id' => $user_id,
                'request_type' => $request_type,
                'requested_at' => $requested_at,
                'expire_at' => $expire_at
            ]
        );
    }

    /**
     * @param Request $request
     * @param string $user_id
     *
     * @return bool
     */
    public static function validateUrl(Request $request, string $user_id)
    {
        if (!($queries = HttpUri::createFromString($request->fullUrl())->getQuery()))
            return false;

        $urlValues = self::decryptUrl(Query::extract($queries));

        /** @var SignedUrls $userUrl */
        $userUrl = SignedUrls::where('user_id', $user_id)->where('request_type', $urlValues[self::$request_type_raw_value])->first();
        $is_expired = Carbon::createFromTimestamp($urlValues[self::$expire_at_raw_value])->lessThan(Carbon::now());

        if (!$userUrl or $userUrl->url_signature != $urlValues[self::$signature_raw_value]
            or $userUrl->expire_at != $urlValues[self::$expire_at_raw_value]
            or $is_expired or $urlValues[self::$request_type_raw_value] != $userUrl->request_type)
            return false;

        return true;
    }

    /**
     * @param array $queries
     *
     * @return array
     */
    private static function decryptUrl(array $queries)
    {
        $queriesKeys = array_keys($queries);
        $decryptedQueries = array();
        $queriesFunctions = self::initFunctionArray();

        foreach ($queriesKeys as $queriesKey)
        {
            $key = Crypt::decrypt($queriesKey);
            $decryptedQueries[$key] = $queries[$queriesKey];
            $decryptedQueries[$key] = self::executeFunctionArray($queriesFunctions, $decryptedQueries, $key);
        }

        return $decryptedQueries;
    }

    /**
     * @return array
     */
    private static function initFunctionArray()
    {
        $queriesFunctions = array();

        $queriesFunctions[self::$signature_raw_value] = array('self::getUrlSignatureFromUrl' => array(get_called_class(), 'getUrlSignatureFromUrl'));
        $queriesFunctions[self::$request_type_raw_value] = array('self::getRequestTypeFromUrl' => array(get_called_class(), 'getRequestTypeFromUrl'));
        $queriesFunctions[self::$expire_at_raw_value] = array('self::getExpireAtFromUrl' => array(get_called_class(), 'getExpireAtFromUrl'));

        return $queriesFunctions;
    }

    private static function executeFunctionArray(array $queriesFunctions, array $decryptedQueries, string $key)
    {
        $iterator = new ArrayIterator($queriesFunctions[$key]);
        $iterator->seek(0);

        return call_user_func($iterator->current(), $decryptedQueries[$key]);
    }

    /**
     * @param string $user_id
     * @param Request $request
     */
    public static function invalidate(string $user_id, Request $request)
    {
        SignedUrls::where('user_id', $user_id)->where('request_type', self::getRequestTypeFromRequest($request))->delete();
    }

    public static function removeExpiredSignedUrl(string $user_uuid, string $request_type)
    {
        SignedUrls::where('user_id', $user_uuid)->where('request_type', $request_type)->delete();
    }

    /**
     * @param Request $request
     * @return mixed|null|string
     */
    private static function getRequestTypeFromRequest(Request $request)
    {
        $queries = HttpUri::createFromString($request->fullUrl())->getQuery();
        $queries = Query::extract($queries);
        $queriesKeys = array_keys($queries);
        $decryptedQueries = array();

        foreach ($queriesKeys as $queriesKey)
        {
            $key = Crypt::decrypt($queriesKey);
            $decryptedQueries[$key] = $queries[$queriesKey];
            if ($key == self::$request_type_raw_value)
                return self::getValueFromKey($decryptedQueries, $key);
        }

        return null;
    }

    /**
     * @param array $decryptedQueries
     * @param       $key
     *
     * @return mixed|string
     */
    private static function getValueFromKey(array $decryptedQueries, $key)
    {
        $queriesFunctions = self::initFunctionArray();

        return self::executeFunctionArray($queriesFunctions, $decryptedQueries, $key);
    }

    public static function invalidateWithRequestTypePattern(string $user_id, Request $request)
    {
        $requestType = self::getRequestTypeFromRequest($request);
        $requestTypePrefix = substr($requestType, 0, strpos($requestType, '_'));
        $requestTypePattern = substr($requestType, strlen($requestType) - strlen(strrchr($requestType, '_')) + 1);

        SignedUrls::where('user_id', $user_id)
            ->whereRaw('request_type like "' . $requestTypePrefix . '%' . $requestTypePattern . '"')->delete();
    }

    /**
     * @param string $user_id
     *
     * @return void
     */
    public static function invalidateAll(string $user_id)
    {
        SignedUrls::where('user_id', $user_id)->delete();
    }

    /**
     * @param string $user_id
     * @param string $request_type
     *
     * @return bool
     */
    public static function urlAlreadyCreated(string $user_id, string $request_type)
    {
        if (($userUrl = SignedUrls::where('user_id', $user_id)->where('request_type', $request_type)->first()))
            return true;
        return false;
    }

    /**
     * @param string $encryptedRequestType
     *
     * @return string
     */
    private static function getRequestTypeFromUrl(string $encryptedRequestType): string
    {
        return str_replace('=', '', Crypt::decrypt($encryptedRequestType));
    }

    /**
     * @param string $urlSignature
     *
     * @return string
     */
    private static function getUrlSignatureFromUrl(string $urlSignature): string
    {
        return str_replace('=', '', $urlSignature);
    }

    /**
     * @param string $expireAt
     *
     * @return int
     */
    private static function getExpireAtFromUrl(string $expireAt): int
    {
        return intval(str_replace('=', '', $expireAt));
    }

}
