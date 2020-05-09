<?php
declare(strict_types=1);

namespace Matchish\ScoutElasticSearch\Jobs\Stages;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Matchish\ScoutElasticSearch\Searchable\ImportSource;

/**
 * @internal
 */
final class PullFromSource
{
    /**
     * @var ImportSource
     */
    private $source;

    /**
     * @param ImportSource $source
     */
    public function __construct(ImportSource $source)
    {
        $this->source = $source;
    }

    public function handle(): void
    {
        $results = $this->source->get()->filter->shouldBeSearchable();
        if (! $results->isEmpty()) {
            $results->first()->searchableUsing()->update($results);
        }
    }

    public function estimate(): int
    {
        return 1;
    }

    public function title(): string
    {
        return 'Indexing...';
    }

    /**
     * @param ImportSource $source
     * @return LazyCollection
     */
    public static function chunked(ImportSource $source): LazyCollection
    {
        /** @var Collection $chunked */
        $chunked = $source->chunked();
        return LazyCollection::make(function () use ($chunked){
            foreach ($chunked as $chunks) {
                yield $chunks->map(function ($chunk) {
                    return new static($chunk);
                });
            }
        });
    }
}
