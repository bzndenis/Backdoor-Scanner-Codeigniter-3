<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backdoor Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/style.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
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

        .highlight {
            color: #ff0;
            font-weight: bold;
        }

        .btn-custom {
            background-color: #555;
            color: #fff;
            border: none;
        }

        .btn-custom:hover {
            background-color: #777;
        }

        .retro-input {
            background-color: #333;
            color: #0f0;
            border: 1px solid #0f0;
        }

        .retro-input::placeholder {
            color: #0f0;
        }

        .retro-btn {
            background-color: #0056b3;
            color: #fff;
            border: 1px solid #0f0;
        }

        .retro-btn:hover {
            background-color: #004494;
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
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center animate__animated animate__fadeInDown">Simple Backdoor Scanner</h1>
        <?php if (isset($error))
            echo '<div class="alert alert-danger animate__animated animate__shakeX">' . $error . '</div>'; ?>
        <form action="<?php echo site_url('scanner/upload'); ?>" method="post" enctype="multipart/form-data"
            class="create-project-form shadow-lg p-4 bg-dark rounded animate__animated animate__fadeInUp">
            <div class="mb-3 form-group">
                <label for="userfile" class="form-label">Choose file to upload Max 10Mb</label>
                <input class="form-control form-input retro-input" type="file" name="userfile" size="20" />
            </div>
            <div class="text-center mt-4">
                <button type="submit" class="btn retro-btn animate__animated animate__pulse animate__infinite">Upload
                    and Scan</button>
            </div>
        </form>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"
        integrity="sha512-6sSYJqDreZRZGkJ3b+YfdhB3MzmuP9R7X1QZ6g5aIXhRvR1Y/N/P47jmnkENm7YL3oqsmI6AK+V6AD99uWDnIw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

</body>

</html>