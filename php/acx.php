<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

class acx extends Exchange {

    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'acx',
            'name' => 'ACX',
            'countries' => 'AU',
            'rateLimit' => 1000,
            'version' => 'v2',
            'has' => array (
                'CORS' => true,
                'fetchTickers' => true,
                'fetchOHLCV' => true,
                'withdraw' => true,
            ),
            'timeframes' => array (
                '1m' => '1',
                '5m' => '5',
                '15m' => '15',
                '30m' => '30',
                '1h' => '60',
                '2h' => '120',
                '4h' => '240',
                '12h' => '720',
                '1d' => '1440',
                '3d' => '4320',
                '1w' => '10080',
            ),
            'urls' => array (
                'logo' => 'https://user-images.githubusercontent.com/1294454/30247614-1fe61c74-9621-11e7-9e8c-f1a627afa279.jpg',
                'extension' => '.json',
                'api' => 'https://acx.io/api',
                'www' => 'https://acx.io',
                'doc' => 'https://acx.io/documents/api_v2',
            ),
            'api' => array (
                'public' => array (
                    'get' => array (
                        'markets', // Get all available markets
                        'tickers', // Get ticker of all markets
                        'tickers/{market}', // Get ticker of specific market
                        'trades', // Get recent trades on market, each trade is included only once Trades are sorted in reverse creation order.
                        'order_book', // Get the order book of specified market
                        'depth', // Get depth or specified market Both asks and bids are sorted from highest price to lowest.
                        'k', // Get OHLC(k line) of specific market
                        'k_with_pending_trades', // Get K data with pending trades, which are the trades not included in K data yet, because there's delay between trade generated and processed by K data generator
                        'timestamp', // Get server current time, in seconds since Unix epoch
                    ),
                ),
                'private' => array (
                    'get' => array (
                        'members/me', // Get your profile and accounts info
                        'deposits', // Get your deposits history
                        'deposit', // Get details of specific deposit
                        'deposit_address', // Where to deposit The address field could be empty when a new address is generating (e.g. for bitcoin), you should try again later in that case.
                        'orders', // Get your orders, results is paginated
                        'order', // Get information of specified order
                        'trades/my', // Get your executed trades Trades are sorted in reverse creation order.
                        'withdraws', // Get your cryptocurrency withdraws
                        'withdraw', // Get your cryptocurrency withdraw
                    ),
                    'post' => array (
                        'orders', // Create a Sell/Buy order
                        'orders/multi', // Create multiple sell/buy orders
                        'orders/clear', // Cancel all my orders
                        'order/delete', // Cancel an order
                        'withdraw', // Create a withdraw
                    ),
                ),
            ),
            'fees' => array (
                'trading' => array (
                    'tierBased' => false,
                    'percentage' => true,
                    'maker' => 0.2 / 100,
                    'taker' => 0.2 / 100,
                ),
                'funding' => array (
                    'tierBased' => false,
                    'percentage' => true,
                    'withdraw' => 0.0, // There is only 1% fee on withdrawals to your bank account.
                ),
            ),
            'exceptions' => array (
                2002 => '\\ccxt\\InsufficientFunds',
                2003 => '\\ccxt\\OrderNotFound',
            ),
        ));
    }

    public function fetch_markets () {
        $markets = $this->publicGetMarkets ();
        $result = array ();
        for ($p = 0; $p < count ($markets); $p++) {
            $market = $markets[$p];
            $id = $market['id'];
            $symbol = $market['name'];
            list ($base, $quote) = explode ('/', $symbol);
            $base = $this->common_currency_code($base);
            $quote = $this->common_currency_code($quote);
            $result[] = array (
                'id' => $id,
                'symbol' => $symbol,
                'base' => $base,
                'quote' => $quote,
                'info' => $market,
            );
        }
        return $result;
    }

    public function fetch_balance ($params = array ()) {
        $this->load_markets();
        $response = $this->privateGetMembersMe ();
        $balances = $response['accounts'];
        $result = array ( 'info' => $balances );
        for ($b = 0; $b < count ($balances); $b++) {
            $balance = $balances[$b];
            $currency = $balance['currency'];
            $uppercase = strtoupper ($currency);
            $account = array (
                'free' => floatval ($balance['balance']),
                'used' => floatval ($balance['locked']),
                'total' => 0.0,
            );
            $account['total'] = $this->sum ($account['free'], $account['used']);
            $result[$uppercase] = $account;
        }
        return $this->parse_balance($result);
    }

    public function fetch_order_book ($symbol, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $orderbook = $this->publicGetDepth (array_merge (array (
            'market' => $market['id'],
            'limit' => 300,
        ), $params));
        $timestamp = $orderbook['timestamp'] * 1000;
        $result = $this->parse_order_book($orderbook, $timestamp);
        $result['bids'] = $this->sort_by($result['bids'], 0, true);
        $result['asks'] = $this->sort_by($result['asks'], 0);
        return $result;
    }

    public function parse_ticker ($ticker, $market = null) {
        $timestamp = $ticker['at'] * 1000;
        $ticker = $ticker['ticker'];
        $symbol = null;
        if ($market)
            $symbol = $market['symbol'];
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => $this->safe_float($ticker, 'high', null),
            'low' => $this->safe_float($ticker, 'low', null),
            'bid' => $this->safe_float($ticker, 'buy', null),
            'ask' => $this->safe_float($ticker, 'sell', null),
            'vwap' => null,
            'open' => null,
            'close' => null,
            'first' => null,
            'last' => $this->safe_float($ticker, 'last', null),
            'change' => null,
            'percentage' => null,
            'average' => null,
            'baseVolume' => $this->safe_float($ticker, 'vol', null),
            'quoteVolume' => null,
            'info' => $ticker,
        );
    }

    public function fetch_tickers ($symbols = null, $params = array ()) {
        $this->load_markets();
        $tickers = $this->publicGetTickers ($params);
        $ids = is_array ($tickers) ? array_keys ($tickers) : array ();
        $result = array ();
        for ($i = 0; $i < count ($ids); $i++) {
            $id = $ids[$i];
            $market = null;
            $symbol = $id;
            if (is_array ($this->markets_by_id) && array_key_exists ($id, $this->markets_by_id)) {
                $market = $this->markets_by_id[$id];
                $symbol = $market['symbol'];
            } else {
                $base = mb_substr ($id, 0, 3);
                $quote = mb_substr ($id, 3, 6);
                $base = strtoupper ($base);
                $quote = strtoupper ($quote);
                $base = $this->common_currency_code($base);
                $quote = $this->common_currency_code($quote);
                $symbol = $base . '/' . $quote;
            }
            $ticker = $tickers[$id];
            $result[$symbol] = $this->parse_ticker($ticker, $market);
        }
        return $result;
    }

    public function fetch_ticker ($symbol, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $response = $this->publicGetTickersMarket (array_merge (array (
            'market' => $market['id'],
        ), $params));
        return $this->parse_ticker($response, $market);
    }

    public function parse_trade ($trade, $market = null) {
        $timestamp = $this->parse8601 ($trade['created_at']);
        return array (
            'id' => (string) $trade['id'],
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'symbol' => $market['symbol'],
            'type' => null,
            'side' => null,
            'price' => $this->safe_float($trade, 'price'),
            'amount' => $this->safe_float($trade, 'volume'),
            'cost' => $this->safe_float($trade, 'funds'),
            'info' => $trade,
        );
    }

    public function fetch_trades ($symbol, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $response = $this->publicGetTrades (array_merge (array (
            'market' => $market['id'],
        ), $params));
        return $this->parse_trades($response, $market, $since, $limit);
    }

    public function parse_ohlcv ($ohlcv, $market = null, $timeframe = '1m', $since = null, $limit = null) {
        return [
            $ohlcv[0] * 1000,
            $ohlcv[1],
            $ohlcv[2],
            $ohlcv[3],
            $ohlcv[4],
            $ohlcv[5],
        ];
    }

    public function fetch_ohlcv ($symbol, $timeframe = '1m', $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        if (!$limit)
            $limit = 500; // default is 30
        $request = array (
            'market' => $market['id'],
            'period' => $this->timeframes[$timeframe],
            'limit' => $limit,
        );
        if ($since !== null)
            $request['timestamp'] = $since;
        $response = $this->publicGetK (array_merge ($request, $params));
        return $this->parse_ohlcvs($response, $market, $timeframe, $since, $limit);
    }

    public function parse_order ($order, $market = null) {
        $symbol = null;
        if ($market) {
            $symbol = $market['symbol'];
        } else {
            $marketId = $order['market'];
            $symbol = $this->marketsById[$marketId]['symbol'];
        }
        $timestamp = $this->parse8601 ($order['created_at']);
        $state = $order['state'];
        $status = null;
        if ($state === 'done') {
            $status = 'closed';
        } else if ($state === 'wait') {
            $status = 'open';
        } else if ($state === 'cancel') {
            $status = 'canceled';
        }
        return array (
            'id' => (string) $order['id'],
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'status' => $status,
            'symbol' => $symbol,
            'type' => $order['ord_type'],
            'side' => $order['side'],
            'price' => floatval ($order['price']),
            'amount' => floatval ($order['volume']),
            'filled' => floatval ($order['executed_volume']),
            'remaining' => floatval ($order['remaining_volume']),
            'trades' => null,
            'fee' => null,
            'info' => $order,
        );
    }

    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        $this->load_markets();
        $order = array (
            'market' => $this->market_id($symbol),
            'side' => $side,
            'volume' => (string) $amount,
            'ord_type' => $type,
        );
        if ($type === 'limit') {
            $order['price'] = (string) $price;
        }
        $response = $this->privatePostOrders (array_merge ($order, $params));
        $market = $this->marketsById[$response['market']];
        return $this->parse_order($response, $market);
    }

    public function cancel_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        $result = $this->privatePostOrderDelete (array ( 'id' => $id ));
        $order = $this->parse_order($result);
        $status = $order['status'];
        if ($status === 'closed' || $status === 'canceled') {
            throw new OrderNotFound ($this->id . ' ' . $this->json ($order));
        }
        return $order;
    }

    public function withdraw ($currency, $amount, $address, $tag = null, $params = array ()) {
        $this->load_markets();
        $result = $this->privatePostWithdraw (array_merge (array (
            'currency' => strtolower ($currency),
            'sum' => $amount,
            'address' => $address,
        ), $params));
        return array (
            'info' => $result,
            'id' => null,
        );
    }

    public function nonce () {
        return $this->milliseconds ();
    }

    public function encode_params ($params) {
        if (is_array ($params) && array_key_exists ('orders', $params)) {
            $orders = $params['orders'];
            $query = $this->urlencode ($this->keysort ($this->omit ($params, 'orders')));
            for ($i = 0; $i < count ($orders); $i++) {
                $order = $orders[$i];
                $keys = is_array ($order) ? array_keys ($order) : array ();
                for ($k = 0; $k < count ($keys); $k++) {
                    $key = $keys[$k];
                    $value = $order[$key];
                    $query .= '&$orders%5B%5D%5B' . $key . '%5D=' . (string) $value;
                }
            }
            return $query;
        }
        return $this->urlencode ($this->keysort ($params));
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $request = '/api' . '/' . $this->version . '/' . $this->implode_params($path, $params);
        if (is_array ($this->urls) && array_key_exists ('extension', $this->urls))
            $request .= $this->urls['extension'];
        $query = $this->omit ($params, $this->extract_params($path));
        $url = $this->urls['api'] . $request;
        if ($api === 'public') {
            if ($query) {
                $url .= '?' . $this->urlencode ($query);
            }
        } else {
            $this->check_required_credentials();
            $nonce = (string) $this->nonce ();
            $query = $this->encode_params (array_merge (array (
                'access_key' => $this->apiKey,
                'tonce' => $nonce,
            ), $params));
            $auth = $method . '|' . $request . '|' . $query;
            $signature = $this->hmac ($this->encode ($auth), $this->encode ($this->secret));
            $suffix = $query . '&$signature=' . $signature;
            if ($method === 'GET') {
                $url .= '?' . $suffix;
            } else {
                $body = $suffix;
                $headers = array ( 'Content-Type' => 'application/x-www-form-urlencoded' );
            }
        }
        return array ( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function handle_errors ($code, $reason, $url, $method, $headers, $body) {
        if ($code === 400) {
            $response = json_decode ($body, $as_associative_array = true);
            $error = $this->safe_value($response, 'error');
            $errorCode = $this->safe_string($error, 'code');
            $feedback = $this->id . ' ' . $this->json ($response);
            $exceptions = $this->exceptions;
            if (is_array ($exceptions) && array_key_exists ($errorCode, $exceptions)) {
                throw new $exceptions[$errorCode] ($feedback);
            }
            // fallback to default $error handler
        }
    }
}
