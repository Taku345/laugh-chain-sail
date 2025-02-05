<?php

class SymbolService
{
    private $facade;
    private $transactionRoutesApi;
    private $officialAccount;

    public function __construct()
    {
        $symbol = app('symbol.config');
        $this->facade = $symbol['facade'];
        $this->transactionRoutesApi = $symbol['transactionRoutesApi'];
        $this->officialAccount = $symbol['officialAccount'];
    }

    public function getFacade()
    {
        return $this->facade;
    }

    public function getTransactionRoutesApi()
    {
        return $this->transactionRoutesApi;
    }

    public function getOfficialAccount()
    {
        return $this->officialAccount;
    }
}
