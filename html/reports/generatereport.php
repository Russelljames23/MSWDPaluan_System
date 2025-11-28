<?php
require_once "../../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data of Senior Citizen 2024</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        body {
            font-family: "Times New Roman", serif;
            background-color: #fff;
            margin: 40px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            width: 80px;
            display: block;
            margin: 0 auto;
        }

        .header h2,
        .header h3,
        .header h4 {
            margin: 2px 0;
        }

        .title {
            text-align: center;
            font-weight: bold;
            margin: 20px 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th,
        td {
            border: 1px solid #000;
            text-align: center;
            padding: 6px;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }
    </style>
</head>

<body>
    <main>
        <section class="bg-white dark:bg-gray-900 mx-auto max-w-4xl p-10  shadow-lg  ">
            <!-- Header -->
                <div class="flex items-center justify-center mb-6">
                    <!-- Left: Logo -->
                    <div class="flex-shrink-0 mr-6">
                        <img src="../../img/paluan.png" alt="Seal" class="w-24">
                    </div>
                    <!-- Right: Text -->
                    <div class="text-center font-semibold justify-center">
                        <h4 class=" text-gray-800 dark:text-gray-200 text-sm leading-tight">Republic of the
                            Philippines</h4>
                        <h4 class=" text-gray-800 dark:text-gray-200 text-sm leading-tight">PROVINCE OF
                            OCCIDENTAL
                            MINDORO</h4>
                        <h4 class=" text-gray-800 dark:text-gray-200 text-sm leading-tight">Municipality of
                            Paluan
                        </h4>
                        <h3 class=" text-gray-900 dark:text-white text-lg  mt-2">OFFICE OF THE
                            SENIOR
                            CITIZENS AFFAIRS (OSCA)</h3>
                    </div>
                </div>
            

            <!-- Title -->
            <div class="text-center mb-4">
                <h2 class="font-normal text-lg  text-gray-800 dark:text-white pt-10 font-semibold ">
                    DATA OF SENIOR CITIZEN 2024
                </h2>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full border border-gray-400 text-sm font-normal text-gray-800 dark:text-gray-200">
                    <thead class="bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <tr>
                            <th class="border border-gray-400 px-4 py-2 text-left"> </th>
                            <th class="border border-gray-400 px-4 py-2 text-center">MALE</th>
                            <th class="border border-gray-400 px-4 py-2 text-center">FEMALE</th>
                            <th class="border border-gray-400 px-4 py-2 text-center">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Availed of OSCA ID (New)</td>
                            <td class="border border-gray-400 text-center">48</td>
                            <td class="border border-gray-400 text-center">85</td>
                            <td class="border border-gray-400 text-center">133</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Availed of SP</td>
                            <td class="border border-gray-400 text-center">715</td>
                            <td class="border border-gray-400 text-center">583</td>
                            <td class="border border-gray-400 text-center">1298</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Availed of LSP (SSS/GSIS)</td>
                            <td class="border border-gray-400 text-center">147</td>
                            <td class="border border-gray-400 text-center">191</td>
                            <td class="border border-gray-400 text-center">338</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">LSP Non Pensioners</td>
                            <td class="border border-gray-400 text-center">75</td>
                            <td class="border border-gray-400 text-center">80</td>
                            <td class="border border-gray-400 text-center">155</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Availed of AICS</td>
                            <td class="border border-gray-400 text-center">124</td>
                            <td class="border border-gray-400 text-center">139</td>
                            <td class="border border-gray-400 text-center">263</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Availed of Birthday Gift</td>
                            <td class="border border-gray-400 text-center">954</td>
                            <td class="border border-gray-400 text-center">1097</td>
                            <td class="border border-gray-400 text-center">2051</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Availed Milestone</td>
                            <td class="border border-gray-400 text-center">92</td>
                            <td class="border border-gray-400 text-center">88</td>
                            <td class="border border-gray-400 text-center">180</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of Bedridden SC</td>
                            <td class="border border-gray-400 text-center">7</td>
                            <td class="border border-gray-400 text-center">16</td>
                            <td class="border border-gray-400 text-center">23</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Availed of Burial Assistance</td>
                            <td class="border border-gray-400 text-center">27</td>
                            <td class="border border-gray-400 text-center">42</td>
                            <td class="border border-gray-400 text-center">69</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Availed of Medical Assistance
                                (₱5,000.00 with wheelchair)</td>
                            <td class="border border-gray-400 text-center">4</td>
                            <td class="border border-gray-400 text-center">5</td>
                            <td class="border border-gray-400 text-center">9</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Centenarian Awardee (₱50,000.00)
                            </td>
                            <td class="border border-gray-400 text-center">0</td>
                            <td class="border border-gray-400 text-center">2</td>
                            <td class="border border-gray-400 text-center">2</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Availed (Provision of Medical
                                Assistance) ₱1,000.00 (Brgy. Mananao)</td>
                            <td class="border border-gray-400 text-center">32</td>
                            <td class="border border-gray-400 text-center">39</td>
                            <td class="border border-gray-400 text-center">71</td>
                        </tr>
                        <tr class="bg-gray-100 font-semibold">
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Availed of Christmas Gift</td>
                            <td class="border border-gray-400 text-center">954</td>
                            <td class="border border-gray-400 text-center">1097</td>
                            <td class="border border-gray-400 text-center">2051</td>
                        </tr>
                        <tr class="bg-gray-200 font-bold">
                            <td class="border border-gray-400 px-4 py-2">Total # of SC Served</td>
                            <td class="border border-gray-400 text-center">954</td>
                            <td class="border border-gray-400 text-center">1097</td>
                            <td class="border border-gray-400 text-center">2051</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>

</html>