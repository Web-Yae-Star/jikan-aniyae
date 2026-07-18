<?php

namespace App\Services;

use App\Anime;
use Illuminate\Pagination\LengthAwarePaginator;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Search\AnimeSearchRequest;
use Jikan\Request\Anime\AnimeRequest;

final class LiveScrapingSearchService
{
    private MalClient $jikan;

    public function __construct()
    {
        $this->jikan = new MalClient();
    }

    public function searchAnime(string $query, int $page = 1, int $limit = 25, ?string $orderBy = null, bool $sortDesc = false): LengthAwarePaginator
    {
        $request = new AnimeSearchRequest($query, $page);
        $result = $this->jikan->getAnimeSearch($request);

        $items = collect($result->getResults())->map(function ($item) {
            $malId = $item->getMalId();

            $cached = Anime::find($malId);
            if ($cached) {
                return $cached;
            }

            try {
                $full = $this->jikan->getAnime(new AnimeRequest($malId));
                $data = $this->mapFullAnime($full);

                Anime::updateOrCreate(
                    ['mal_id' => $data->mal_id],
                    (array) $data
                );

                return $data;
            } catch (\Exception $e) {
                return $this->mapSearchItem($item);
            }
        });

        if ($orderBy !== null && $items->isNotEmpty()) {
            $items = $items->sortBy($orderBy, SORT_REGULAR, $sortDesc)->values();
        }

        $total = $items->count();
        $lastPage = $result->getLastVisiblePage() ?: 1;
        $currentPage = $page;

        $paginatedItems = $items->slice(0, $limit)->values();

        return new LengthAwarePaginator(
            $paginatedItems,
            $total,
            $limit,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    private function mapFullAnime($anime): \stdClass
    {
        $premiered = $anime->getPremiered();

        $obj = new \stdClass();
        $obj->mal_id = $anime->getMalId();
        $obj->url = $anime->getUrl();
        $obj->images = $this->serializeImages($anime->getImages());
        $obj->trailer = $this->serializeTrailer($anime->getTrailer());
        $obj->approved = $anime->isApproved();
        $obj->titles = $this->serializeTitles($anime->getTitles());
        $obj->title = $anime->getTitle();
        $obj->title_english = $anime->getTitleEnglish();
        $obj->title_japanese = $anime->getTitleJapanese();
        $obj->title_synonyms = $anime->getTitleSynonyms();
        $obj->type = $anime->getType();
        $obj->source = $anime->getSource();
        $obj->episodes = $anime->getEpisodes();
        $obj->status = $anime->getStatus();
        $obj->airing = $anime->isAiring();
        $obj->aired = $this->serializeDateRange($anime->getAired());
        $obj->duration = $anime->getDuration();
        $obj->rating = $anime->getRating();
        $obj->score = $anime->getScore();
        $obj->scored_by = $anime->getScoredBy();
        $obj->rank = $anime->getRank();
        $obj->popularity = $anime->getPopularity();
        $obj->members = $anime->getMembers();
        $obj->favorites = $anime->getFavorites();
        $obj->synopsis = $anime->getSynopsis();
        $obj->background = $anime->getBackground();
        $obj->premiered = $premiered;
        $obj->season = $this->extractSeason($premiered);
        $obj->year = $this->extractYear($premiered);
        $obj->broadcast = $anime->getBroadcast();
        $obj->producers = $this->serializeMalUrls($anime->getProducers());
        $obj->licensors = $this->serializeMalUrls($anime->getLicensors());
        $obj->studios = $this->serializeMalUrls($anime->getStudios());
        $obj->genres = $this->serializeMalUrls($anime->getGenres());
        $obj->explicit_genres = $this->serializeMalUrls($anime->getExplicitGenres());
        $obj->themes = $this->serializeMalUrls($anime->getThemes());
        $obj->demographics = $this->serializeMalUrls($anime->getDemographics());
        $obj->related = [];
        $obj->opening_themes = [];
        $obj->ending_themes = [];
        return $obj;
    }

    private function mapSearchItem($item): \stdClass
    {
        $obj = new \stdClass();
        $obj->mal_id = $item->getMalId();
        $obj->url = $item->getUrl();
        $obj->images = $this->serializeImages($item->getImages());
        $obj->trailer = null;
        $obj->approved = null;
        $obj->titles = [];
        $obj->title = $item->getTitle();
        $obj->title_english = null;
        $obj->title_japanese = null;
        $obj->title_synonyms = [];
        $obj->type = $item->getType();
        $obj->source = null;
        $obj->episodes = $item->getEpisodes();
        $obj->status = null;
        $obj->airing = $item->isAiring();
        $obj->aired = null;
        $obj->duration = null;
        $obj->rating = $item->getRated();
        $obj->score = $item->getScore();
        $obj->scored_by = null;
        $obj->rank = null;
        $obj->popularity = null;
        $obj->members = $item->getMembers();
        $obj->favorites = null;
        $obj->synopsis = $item->getSynopsis();
        $obj->background = null;
        $obj->premiered = null;
        $obj->season = null;
        $obj->year = null;
        $obj->broadcast = null;
        $obj->producers = null;
        $obj->licensors = null;
        $obj->studios = null;
        $obj->genres = null;
        $obj->explicit_genres = null;
        $obj->themes = null;
        $obj->demographics = null;
        $obj->related = [];
        $obj->opening_themes = [];
        $obj->ending_themes = [];
        return $obj;
    }

    private function serializeImages($images): array
    {
        if (!$images) {
            return ['jpg' => null, 'webp' => null];
        }

        $result = [];

        $jpg = $images->getJpg();
        if ($jpg) {
            $result['jpg'] = [
                'image_url' => $jpg->getImageUrl(),
                'small_image_url' => $jpg->getSmallImageUrl(),
                'large_image_url' => $jpg->getLargeImageUrl(),
            ];
        }

        $webp = $images->getWebp();
        if ($webp) {
            $result['webp'] = [
                'image_url' => $webp->getImageUrl(),
                'small_image_url' => $webp->getSmallImageUrl(),
                'large_image_url' => $webp->getLargeImageUrl(),
            ];
        }

        return $result;
    }

    private function serializeTrailer($trailer): ?array
    {
        if (!$trailer) {
            return null;
        }

        $images = null;
        if ($trailer->getImages()) {
            $ti = $trailer->getImages();
            $images = [
                'image_url' => $ti->getImageUrl(),
                'small_image_url' => $ti->getSmallImageUrl(),
                'medium_image_url' => $ti->getMediumImageUrl(),
                'large_image_url' => $ti->getLargeImageUrl(),
                'maximum_image_url' => $ti->getMaximumImageUrl(),
            ];
        }

        return [
            'youtube_id' => $trailer->getYoutubeId(),
            'url' => $trailer->getUrl(),
            'embed_url' => $trailer->getEmbedUrl(),
            'images' => $images,
        ];
    }

    private function serializeDateRange($dateRange): ?array
    {
        if (!$dateRange) {
            return null;
        }

        $from = $dateRange->getFrom();
        $to = $dateRange->getUntil();
        $fromProp = $dateRange->getFromProp();
        $untilProp = $dateRange->getUntilProp();

        $formatProp = fn($prop) => $prop ? [
            'day' => $prop->getDay(),
            'month' => $prop->getMonth(),
            'year' => $prop->getYear(),
        ] : null;

        $fromStr = $from ? $from->format('M j, Y') : null;
        $toStr = $to ? $to->format('M j, Y') : null;
        $string = $fromStr && $toStr ? "$fromStr to $toStr"
            : ($fromStr ? $fromStr : null);

        return [
            'from' => $from ? $from->format('Y-m-d\TH:i:sP') : null,
            'to' => $to ? $to->format('Y-m-d\TH:i:sP') : null,
            'prop' => [
                'from' => $formatProp($fromProp),
                'to' => $formatProp($untilProp),
            ],
            'string' => $string,
        ];
    }

    private function extractSeason(?string $premiered): ?string
    {
        if (!$premiered || !is_string($premiered)) {
            return null;
        }

        if (preg_match('/^(Winter|Spring|Summer|Fall)/i', $premiered, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }

    private function extractYear(?string $premiered): ?int
    {
        if (!$premiered || !is_string($premiered)) {
            return null;
        }

        if (preg_match('/(\d{4})/', $premiered, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function serializeTitles($titles): array
    {
        if (!$titles) {
            return [];
        }

        return array_map(function ($title) {
            return [
                'type' => $title->getType(),
                'title' => $title->getTitle(),
            ];
        }, $titles);
    }

    private function serializeMalUrls($items): array
    {
        if (!$items) {
            return [];
        }

        return array_map(function ($item) {
            return [
                'mal_id' => $item->getMalId(),
                'type' => $item->getType(),
                'name' => $item->getName(),
                'url' => $item->getUrl(),
            ];
        }, $items);
    }
}
