<?php

namespace App\Console\Commands\Github;

use App\Models\GithubContribution;
use Illuminate\Console\Command;
use GitHub;
use Github\HttpClient\Message\ResponseMediator;

class Contribution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:contribution';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update github contribution';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $githubContributionModel = new GithubContribution();
        // 获取全部的用户数据
        $userData = $githubContributionModel
            ->select('id', 'nickname')
            ->get()
            ->toArray();
        foreach ($userData as $k => $v) {
            $pushData = [];
            for ($i = 1; $i <= 3; $i++) {
                $response = GitHub::getHttpClient()->get('users/'.$v['nickname'].'/events/public?page='.$i.'&per_page=300');
                $events = ResponseMediator::getContent($response);
                array_walk($events, function (&$v) {
                    $v['created_at'] = date('Y-m-d', strtotime($v['created_at']));
                });
                $pushData = collect($events)->where('type', 'PushEvent')->merge($pushData);
            }
            $pushDataArray = $pushData->sortByDesc('created_at')->groupBy('created_at')->map(function ($v) {
                return array_sum(array_column(array_column($v->toArray(), 'payload'), 'distinct_size'));
            })->toArray();
            $map = [
                'id' => $v['id']
            ];
            $data = [
                'content' => $pushDataArray
            ];
            $githubContributionModel->editData($map, $data);
        }
        $this->info('更新完成');
    }
}
