<?php

use Spatie\ResponseCache\Hasher\DefaultHasher;
use Spatie\ResponseCache\Replacers\CsrfTokenReplacer;
use Spatie\ResponseCache\Serializers\DefaultSerializer;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

return [
    /*
     * Determine if the response cache middleware should be enabled.
     */
    'enabled' => env('RESPONSE_CACHE_ENABLED', true),

    /*
     *  The given class will determine if a request should be cached. The
     *  default class will cache all successful GET-requests.
     */
    'cache_profile' => CacheAllSuccessfulGetRequests::class,

    /*
     *  Optionally, you can specify a header that will force a cache bypass.
     */
    'cache_bypass_header' => [
        'name' => env('CACHE_BYPASS_HEADER_NAME', null),
        'value' => env('CACHE_BYPASS_HEADER_VALUE', null),
    ],

    /*
     * When using the default CacheRequestFilter this setting controls the
     * default number of seconds responses must be cached.
     */
    'cache_lifetime_in_seconds' => (int) env('RESPONSE_CACHE_LIFETIME', 60 * 60 * 24 * 7),

    /*
     * This setting determines if a http header named with the cache time
     * should be added to a cached response.
     */
    'add_cache_time_header' => env('APP_DEBUG', false),

    /*
     * This setting determines the name of the http header that contains
     * the time at which the response was cached.
     */
    'cache_time_header_name' => env('RESPONSE_CACHE_HEADER_NAME', 'laravel-responsecache'),

    /*
     * This setting determines if a http header named with the cache age
     * should be added to a cached response.
     */
    'add_cache_age_header' => env('RESPONSE_CACHE_AGE_HEADER', false),

    /*
     * This setting determines the name of the http header that contains the age of cache.
     */
    'cache_age_header_name' => env('RESPONSE_CACHE_AGE_HEADER_NAME', 'laravel-responsecache-age'),

    /*
     * Here you may define the cache store that should be used to store requests.
     */
    'cache_store' => env('RESPONSE_CACHE_DRIVER', 'file'),

    /*
     * Here you may define replacers that dynamically replace content from the response.
     * Each replacer must implement the Replacer interface.
     */
    'replacers' => [
        CsrfTokenReplacer::class,
    ],

    /*
     * If the cache driver supports tags, you may specify a tag name here.
     * You may use a string or an array here.
     */
    'cache_tag' => '',

    /*
     * This class is responsible for generating a hash for a request.
     */
    'hasher' => DefaultHasher::class,

    /*
     * This class is responsible for serializing responses.
     */
    'serializer' => DefaultSerializer::class,
];
