{namespace name="backend/_base/mondu_layout"}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        [disabled] { cursor: not-allowed; opacity: .3 }
        * {
            font-family: Arial, serif;
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-size; 16px;
        }
        .mondu-navbar {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 1rem 5rem;
            box-shadow: rgb(60 64 67 / 0%) 0 1px 2px 0, rgb(60 64 67 / 6%) 0 1px 3px 1px;
            z-index: 1;
        }
        .mondu-navbar-fixed-top {
            position: sticky;
            top: 0;
        }
        .mondu-navbar-brand {
            display: inline-block;
        }
        .mondu-container {
            padding: 1rem 5rem;
        }
        table {
            width: 100%;
            border-spacing: 0;
        }
        th {
            text-align: left;
            padding-bottom: 1rem;
        }
        td {
            padding: 1rem 0 1rem .3rem;
        }
        tbody tr:nth-child(odd){
            background-color: #ecf5ff;
        }
        button, .mondu-button {
            text-decoration: none;
            background-color: #e8e8e8;
            border-radius: 8px;
            border-width: 0;
            color: #333333;
            cursor: pointer;
            display: inline-block;
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            list-style: none;
            margin: 0;
            padding: 10px 12px;
            text-align: center;
            transition: all 200ms;
            vertical-align: baseline;
            white-space: nowrap;
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
        }
        button.primary, .mondu-button.primary {
            background-color: #46086d;
            color: white;
        }
        button.small, .mondu-button.small {
            font-size: 12px;
            padding: 5px 12px;
        }
        ul {
            list-style-type: none;
            display: flex;
            padding-left: 0;
        }
        li {
            width: 32px;
            height: 32px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 4px;
            margin-right: .5rem;
        }
        li.active {
            background-color: #46086d;
        }
        li>a {
            text-decoration: none;
        }
        li.active>a{
            color: white;
        }

        .mondu-group {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .mondu-column-small {
            width: 160px;
        }
        h3 {
            margin-bottom: .5rem;
        }
        hr {
            opacity: .3;
        }
    </style>
</head>
<body role="document">

<!-- Fixed mondu-navbar -->
<nav class="mondu-navbar mondu-navbar-fixed-top" style="min-height: 64px; background: white;">
    <div>
        <div class="mondu-navbar-header">
            <a class="mondu-navbar-brand" href="{url controller="MonduOverview" action="index" __csrf_token=$csrfToken}">
                <img style="height: 30px; display: inline-block" src="https://checkout.mondu.ai/logo.svg" />
            </a>
        </div>
    </div>
</nav>

<div class="mondu-container" role="main">
    {if $errorCode}
        <div class="alert alert-danger" role="alert">{$errorCode|snippet:$errorCode:'backend/mondu_overview/messages'}</div>
    {/if}
    {block name="content/main"}{/block}
</div> <!-- /mondu-container -->

{block name="content/javascript"}
<script type="text/javascript" src="{link file="backend/_resources/js/mondu.js"}"></script>
<script type="text/javascript" src="{link file="backend/base/frame/postmessage-api.js"}"></script>
{/block}

</body>
</html>
