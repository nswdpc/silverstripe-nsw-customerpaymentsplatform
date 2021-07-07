<html>

<head>
    <style type="text/css">
        html, body {
            margin : 0;
            padding : 0;
        }

        body {
            display: block;
            font-family: sans-serif;
            font-size: 1rem;
            color: #222;
            background-color: #fff;
        }

        h1 {
            font-size: 2rem;
        }

        .info {
            display: grid;
            place-items: center;
            width: 100%;
            height: 100%;
        }
        .info > .box {
            display: grid;
            text-align: left;
        }

        .info > .box p {
            vertical-align: middle;
        }

        .info > .box p > span.code {
            border-radius : 0.5rem;
            padding : 0.5rem 1rem;
            color : #fff;
            background-color : #222;
            margin : 0 2rem 0 0;
        }
    </style>
</head>

<body>

<div class="info">
    <div class="box">
        <h1>{$Title.XML}</h1>
        <p><span class="code">{$Code.XML}</span> <span>{$Detail.XML}</span></p>
    </div>
</div>

</body>

</html>
