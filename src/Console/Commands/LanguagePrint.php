<?php

namespace Thorazine\Geo\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Thorazine\Geo\Models\TagTranslation;

class LanguagePrint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'language:print';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach(['en', 'nl'] as $language) {
            $tags = Tag::select('tag_translations.id', 'tag_translations.tag_id', 'tag_translations.title', DB::raw('tags.title as tags_title'))
                ->leftJoin('tag_translations', function($join) use ($language) {
                    $join->on('tag_translations.tag_id', '=', 'tags.id')
                        ->where('tag_translations.language', '=', $language);
                })
                ->orderBy('tags.priority', 'asc')
                ->orderBy('tag_translations.title', 'asc')
                ->get();

            $path = base_path('lang/'.$language);
            if(! is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $content = '<?php'.PHP_EOL.PHP_EOL.'return ['.PHP_EOL;
            foreach($tags as $tag) {
                $content .= '    \''.addslashes($tag->tags_title).'\' => \''. (($tag->title) ? addslashes($tag->title) : addslashes(ucfirst($tag->tags_title))).'\','.PHP_EOL;
            }
            $content .= '];'.PHP_EOL;

            file_put_contents(
                base_path('lang/'.$language.'/tags.php'),
                $content
            );
        }
    }
}
