<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use Exception;
use Throwable;

class YoutubeService
{
    public function searchByKeyword(array $queryParams)
    {
        try {
            $response = Http::withoutVerifying()->get("https://www.googleapis.com/youtube/v3/search", $queryParams);
            /**
             * Check whether or not the Response has items,
             * - If yes return it with the corresponding format
             * - If not return the API's error message
             */
            return array_key_exists('items', json_decode($response->body(), true)) ?
                    $this->formatResponse($response) :
                    new Response($response->body(), $response->status(), ['Content-Type' => 'application/json']);
        } catch (Exception $e) {
            abort($e->getCode(), $e->getMessage());
        } catch (Throwable $t) {
            abort(500);
        }

    }

    private function formatResponse($response)
    {
        // Convert the raw Response's body into an array
        $body = json_decode($response->body(), true);
        // Create the variable that will store all the video's info
        $videos = Array();
        // Go through every element of the List, storing the relevant data in the $videos Array
        foreach($body["items"] as $item)
        {
            $video = [
                "published_at" => $item['snippet']['publishedAt'],
                "id" => $item['id']['videoId'],
                "title" => $item['snippet']['title'],
                "description" => $item['snippet']['description'],
                "thumbnail" => $item['snippet']['thumbnails']['default']['url'],
                "extra" => [
                        "direct_link" => "https://www.youtube.com/watch?v=" . $item['id']['videoId'],
                        "channel_title" => $item['snippet']['channelTitle']
                ]
            ];
            array_push($videos, $video);
        }

        $formattedResponse = Array();

        // If the current page is not the first one, the 'prevPageToken' element is displayed
        $formattedResponse += array_key_exists('prevPageToken', $body) ? ["prev_page_token" => $body['prevPageToken']] : [];

        // If there's more pages, the 'nextPageToken' element is displayed
        $formattedResponse += array_key_exists('nextPageToken', $body) ? ["next_page_token" => $body['nextPageToken']] : [];

        return $formattedResponse += [
            "total_results" => $body['pageInfo']['totalResults'],
            "results_per_page" => $body['pageInfo']['resultsPerPage'],
            "videos" => $videos
        ];
    }
}