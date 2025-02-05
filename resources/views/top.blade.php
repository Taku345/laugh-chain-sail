<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name', 'Laravel') }} - トップページ</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body>
    <header>
        <nav>
            <ul>
                {{-- <li><a href="{{ route('') }}">ホーム</a></li>
                <li><a href="{{ route('') }}">会社概要</a></li>
                <li><a href="{{ route('') }}">お問い合わせ</a></li> --}}
            </ul>
        </nav>
    </header>

    <main>
        <div class="container">
            <h1>LaughChain</h1>
            <pre>
                {{ print_r($accountNFTs, true) }}
            </pre>
        </div>
    </main>

    <footer>
        {{-- <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p> --}}
    </footer>

</body>
</html>
