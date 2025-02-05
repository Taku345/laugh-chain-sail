<?php

namespace App\Console\Commands;

use App\Services\NFTService;
use Illuminate\Console\Command;
use SymbolSdk\Symbol\Address;
use SymbolSdk\Symbol\Models\UnresolvedAddress;

class MintNFTCommand extends Command
{
    protected $signature = 'nft:mint {story_address} {account_address}';
    protected $description = 'NFTを発行してアカウントに送信します';

    public function handle()
    {
        $storyAddress = $this->argument('story_address');
        $accountAddress = new UnresolvedAddress($this->argument('account_address'));

        try {
            $hash = NFTService::mintNFT($storyAddress, $accountAddress);
            $this->info('NFTの発行に成功しました。');
            $this->info("トランザクションハッシュ: {$hash}");
        } catch (\Exception $e) {
            $this->error('NFTの発行に失敗しました。');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
