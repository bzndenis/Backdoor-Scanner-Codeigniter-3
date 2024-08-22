<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/style.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        body {
            background-color: #000;
            color: #0f0;
            font-family: monospace;
        }

        .container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .terminal {
            background-color: #000;
            color: #0f0;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }

        .terminal p {
            margin: 0;
        }

        .terminal hr {
            border-color: #0f0;
        }

        .btn-custom {
            background-color: #555;
            color: #fff;
            border: none;
        }

        .btn-custom:hover {
            background-color: #777;
        }

        .highlight {
            animation: highlight-animation 1s infinite;
        }

        @keyframes highlight-animation {
            0% { background-color: yellow; }
            50% { background-color: red; }
            100% { background-color: yellow; }
        }

        .scrollable {
            max-height: 400px; /* Adjust the height as needed */
            overflow-y: auto;
        }

        footer {
            background-color: #000;
            color: #fff;
            text-align: center;
            padding: 10px 0;
            position: sticky;
            bottom: 0;
            width: 100%;
        }

        .backdoor-list {
        background-color: #000;
        color: #0f0;
        padding: 10px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        overflow-x: auto;
        list-style-type: none; /* Menghilangkan bullet points */
        padding-left: 0; /* Menghilangkan padding default */
    }

    .backdoor-list li {
        margin-bottom: 10px; /* Memberikan jarak antar item */
        white-space: pre-wrap; /* Membungkus teks agar tidak melampaui lebar elemen */
    }

    .backdoor-table {
        width: 100%;
        margin-top: 20px;
    }

    .backdoor-table th, .backdoor-table td {
        padding: 10px;
        text-align: left;
    }

    .backdoor-table th {
        background-color: #333;
    }

    .backdoor-table td {
        background-color: #000;
        color: #0f0;
    }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center animate__animated animate__fadeInDown">Scan Result</h1>
        <?php if (!empty($backdoors)): ?>
            <div class="terminal mt-4 animate__animated animate__fadeInUp">
                <h2>File Kemungkinan Backdoor</h2>
                <table class="table table-dark table-striped backdoor-table">
                    <thead>
                        <tr>
                            <th>Nama File</th>
                            <th>Direktori</th>
                            <th>Pola</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backdoors as $backdoor): ?>
                            <?php
                                // Misalkan $backdoor berisi string dalam format "path/to/file.php => Found (pattern)"
                                $parts = explode(' => ', $backdoor);
                                $filePath = $parts[0];
                                $pattern = isset($parts[1]) ? $parts[1] : '';
                                $fileName = basename($filePath);
                                $directory = dirname($filePath);
                            ?>
                            <tr>
                                <td><?php echo $fileName; ?></td>
                                <td><?php echo $directory; ?></td>
                                <td><?php echo $pattern; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="<?php echo site_url('scanner/export_backdoors'); ?>" class="btn btn-secondary btn-custom">Export to .txt</a>
            </div>
        <?php endif; ?>
        <div class="terminal mt-4 animate__animated animate__fadeInUp scrollable">
            <?php foreach ($result as $res): ?>
                <p class="text-<?php echo $res['color']; ?><?php echo (strpos($res['status'], 'Found') !== false) ? ' alert alert-warning' : ''; ?>">
                    <?php 
                        $status = $res['status'];
                        if (strpos($status, 'Found') !== false) {
                            $status = str_replace('Found', '<span class="highlight">Found</span>', $status);
                        }
                        echo $res['file'] . ' => ' . $status;
                    ?>
                </p>
                <hr>
            <?php endforeach; ?>
        </div>      
        <div class="text-center mt-4">
            <a href="<?php echo site_url('scanner'); ?>" class="btn btn-secondary btn-custom animate__animated animate__pulse animate__infinite">Back</a>
        </div>
    </div>
    <footer class="bg-dark text-white text-center py-3">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y'); ?> Gabut Mode.</p>
            <p class="mb-0">Created by <a href="https://koys.my.id" class="text-white">Koys</a></p>
            <div class="mt-2">
                <a href="https://github.com/bzndenis" class="text-white"><i class="fab fa-github"></i></a>
                <a href="https://instagram.com/dens.akbar" class="text-white"><i class="fab fa-instagram"></i></a>
                <a href="https://koys.my.id" class="text-white"><i class="fas fa-globe"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"
        integrity="sha512-TelkP3PCMJv+viMWynjKcvLsQzx6dJHvIGhfqzFtZKgAjKM1YPqcwzzDEoTc/BHjf43PcPzTQOjuTr4YdE8lNQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        const site_url = '<?= base_url(); ?>';
        const project_id = '<?= isset($project_id) ? $project_id : ''; ?>';
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"
        integrity="sha512-6sSYJqDreZRZGkJ3b+YfdhB3MzmuP9R7X1QZ6g5aIXhRvR1Y/N/P47jmnkENm7YL3oqsmI6AK+V6AD99uWDnIw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script src="<?php echo base_url('assets/js/scripts.js'); ?>"></script>
</body>
</html>