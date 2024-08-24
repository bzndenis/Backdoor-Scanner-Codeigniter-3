<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scanner_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        define('MAX_FILE_SIZE', 200 * 1024 * 1024); // 2MB
    }

    public function scan_directory($dir, $max_depth = 10)
    {
        $files = $this->list_files_generator($dir, $max_depth);
        $results = array();
        $this->detection_results = array();

        foreach ($files as $file) {
            if (is_file($file)) {
                $content = $this->read_file($file);
                $check = $this->check_backdoor($content);

                // Pemeriksaan header palsu
                $file_content = file_get_contents($file);
                if ($this->check_fake_image_header($file_content)) {
                    $this->detection_results[$file]['fake_image_header'] = true;
                    $check['score'] = isset($check['score']) ? $check['score'] + 5 : 5;
                    $check['details'] .= ' (Fake Image Header)';
                }

                $status = empty($check) ? 'Aman' : 'Ditemukan (' . $check['details'] . ')';
                $color = empty($check) ? 'green' : 'red';
                $score = isset($check['score']) ? $check['score'] : 0;
                $suspicion_level = isset($check['suspicion_level']) ? $check['suspicion_level'] : 'Rendah';

                $results[] = array(
                    'file' => $file,
                    'status' => $status,
                    'color' => $color,
                    'score' => $score,
                    'suspicion_level' => $suspicion_level
                );
            }
        }

        return $results;
    }

    private function list_files_generator($dir, $max_depth, $current_depth = 0)
    {
        if ($current_depth >= $max_depth) {
            return array();
        }

        $result = array();
        $files = new \FilesystemIterator($dir);
        foreach ($files as $file) {
            if ($file->isDir()) {
                $subFiles = $this->list_files_generator($file->getPathname(), $max_depth, $current_depth + 1);
                $result = array_merge($result, $subFiles);
            } else {
                $result[] = $file->getPathname();
            }
        }

        return $result;
    }

    private function list_files($dir)
    {
        $scan = array_diff(scandir($dir), array('.', '..'));
        return array_reduce($scan, function ($carry, $item) use ($dir) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $carry = array_merge($carry, $this->list_files($path));
            }
            $carry[] = $path;
            return $carry;
        }, []);
    }

    private function read_file($file)
    {
        if (!is_readable($file)) {
            return "Not readable--";
        }

        if (filesize($file) > MAX_FILE_SIZE) {
            return "Skipped--";
        }

        $content = file_get_contents($file);
        if (!mb_detect_encoding($content, 'UTF-8', true)) {
            return "Non-text file--";
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $binary_extensions = ['ttf', 'otf', 'woff', 'woff2', 'eot', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico', 'pdf'];

        if (in_array($extension, $binary_extensions)) {
            // Check if the binary file contains PHP code
            if (strpos($content, '<?php') !== false) {
                return $this->extract_php_tokens($content);
            }
            return "Binary file--";
        }

        return $this->extract_php_tokens($content);
    }

    private function extract_php_tokens($content)
    {
        $tokens = token_get_all($content);
        return array_unique(array_filter(array_map(function ($token) {
            return is_array($token) ? trim($token[1]) : null;
        }, $tokens)));
    }

    // public function check_backdoor($content) {
    //     if (!is_array($content)) {
    //         return "";
    //     }

    //     $patterns = [
    //         // Fungsi eksekusi kode
    //         'eval', 'system', 'shell_exec', 'exec', 'passthru', 'popen', 'proc_open', 
    //         'pcntl_exec', 'assert', 'preg_replace', 'create_function', 'include', 
    //         'include_once', 'require', 'require_once', 'call_user_func', 'call_user_func_array',

    //         // Fungsi dekode dan enkode
    //         'base64_decode', 'gzinflate', 'str_rot13', 'convert_uu', 'hex2bin', 'bin2hex', 'chr', 'ord',

    //         // Fungsi file dan jaringan
    //         'file_get_contents', 'file_put_contents', 'fopen', 'fwrite', 'unlink', 'readfile', 
    //         'copy', 'rename', 'rmdir', 'mkdir', 'symlink', 'fsockopen', 'pfsockopen', 
    //         'stream_socket_client', 'curl_exec', 'curl_multi_exec', 'move_uploaded_file',

    //         // Fungsi informasi sistem
    //         'phpinfo', 'posix_getpwuid', 'posix_getgrgid', 'posix_uname',

    //         // Fungsi manipulasi sesi dan cookie
    //         'session_start', 'session_destroy', 'setcookie',

    //         // Fungsi berbahaya lainnya
    //         '__halt_compiler', 'extract', 'ini_set', 'ini_alter', 'ini_restore', 'error_reporting', 
    //         'set_time_limit', 'ignore_user_abort',

    //         // Pattern khusus
    //         'FATHURFREAKZ', 'shell_data', 'magicboom', 'r57shell', 'c99shell', 'web shell', 
    //         'webshell', 'backdoor', 'bypass', 'shell', 'hacktools'
    //     ];

    //     $found = array_intersect($patterns, $content);

    //     // Tandai file sebagai backdoor jika ada pattern decode
    //     if (!empty($found)) {
    //         return implode(', ', $found) . ' (Ada Kemungkinan Ini Backdoor Cek Ulang File Tersebut Secara Manual)';
    //     }

    //     return "";
    // }

    public function check_backdoor($content)
    {
        if (!is_array($content)) {
            return "";
        }

        $patterns = [
            // Fungsi eksekusi kode
            'eval',
            'system',
            'shell_exec',
            'exec',
            'passthru',
            'popen',
            'proc_open',
            'pcntl_exec',
            'assert',
            'preg_replace',
            'create_function',
            'include',
            'include_once',
            'require',
            'require_once',
            'call_user_func',
            'call_user_func_array',

            // Fungsi dekode dan enkode
            'base64_decode',
            'gzinflate',
            'str_rot13',
            'convert_uu',
            'hex2bin',
            'bin2hex',
            'chr',
            'ord',

            // Fungsi file dan jaringan
            'file_get_contents',
            'file_put_contents',
            'fopen',
            'fwrite',
            'unlink',
            'readfile',
            'copy',
            'rename',
            'rmdir',
            'mkdir',
            'symlink',
            'fsockopen',
            'pfsockopen',
            'stream_socket_client',
            'curl_exec',
            'curl_multi_exec',
            'move_uploaded_file',

            // Fungsi informasi sistem
            'phpinfo',
            'posix_getpwuid',
            'posix_getgrgid',
            'posix_uname',

            // Fungsi manipulasi sesi dan cookie
            'session_start',
            'session_destroy',
            'setcookie',

            // Fungsi berbahaya lainnya
            '__halt_compiler',
            'extract',
            'ini_set',
            'ini_alter',
            'ini_restore',
            'error_reporting',
            'set_time_limit',
            'ignore_user_abort',

            // Pattern khusus
            'FATHURFREAKZ',
            'shell_data',
            'magicboom',
            'r57shell',
            'c99shell',
            'web shell',
            'webshell',
            'backdoor',
            'bypass',
            'shell',
            'hacktools',

            // Pola untuk mendeteksi backdoor ASP Classic
            'WSCRIPT.SHELL',
            'Scripting.FileSystemObject',
            'WScript.Shell',
            'cmd /c',
            'Server.CreateObject',
            'objShell.exec',
            'getCommandOutput',
            'Request.ServerVariables',
            'Response.Write(Request.ServerVariables',
            'szCMD = request',
            'thisDir = getCommandOutput',

            // Pola tambahan untuk fungsi dan metode berbahaya
            'ExecuteGlobal',
            'Execute',
            'Eval',
            'Run',
            'Shell.Application',
            'ShellExecute',
            'vbscript:',

            // Pola untuk deteksi obfuskasi
            'Replace(',
            'Chr(',
            'ChrW(',
            'String.FromCharCode',
            'StrReverse',
            'Mid(',
            'Left(',
            'Right(',

            // Pola untuk deteksi encoding
            'base64_decode',
            'FromBase64String',
            'Unescape',
        ];

        $whitelist = [
            'include',
            'include_once',
            'require',
            'require_once'
        ];

        $found = array_intersect($patterns, $content);
        $found = array_diff($found, $whitelist);

        $score = 0;
        $detected = [];
        foreach ($found as $pattern) {
            $weight = $this->get_pattern_weight($pattern);
            $score += $weight;
            if ($weight > 0) {
                $detected[] = $pattern . ' (Skor: ' . $weight . ')';
            }
        }

        $suspicion_level = $this->get_suspicion_level($score);

        if ($score > 0) {
            return [
                'details' => implode(', ', $detected),
                'score' => $score,
                'suspicion_level' => $suspicion_level
            ];
        }

        return "";
    }

    private function get_suspicion_level($score)
    {
        if ($score < 10) {
            return 'Rendah (Kemungkinan bukan backdoor)';
        } elseif ($score < 15) {
            return 'Sedang (Perlu diperiksa lebih lanjut)';
        } elseif ($score < 20) {
            return 'Tinggi (Kemungkinan besar backdoor)';
        } else {
            return 'Sangat Tinggi (Hampir pasti backdoor)';
        }
    }

    private function get_pattern_weight($pattern)
    {
        $high_risk = [
            'eval',
            'system',
            'shell_exec',
            'passthru',
            'exec',
            'popen',
            'proc_open',
            'pcntl_exec',
            'assert',
            'create_function',
            'call_user_func',
            'call_user_func_array',
            'preg_replace', // dengan /e modifier
            'mb_ereg_replace', // dengan /e modifier
            'extract', // jika digunakan dengan input yang tidak terpercaya
            'parse_str', // jika digunakan dengan input yang tidak terpercaya
            'putenv',
            'ini_set',
            'ini_alter',
            'ini_restore', // jika digunakan untuk mengubah konfigurasi penting
            'dl', // untuk memuat ekstensi PHP dinamis
            'fsockopen',
            'pfsockopen', // jika digunakan untuk koneksi keluar yang mencurigakan
            'stream_socket_client',
            'move_uploaded_file', // jika tidak divalidasi dengan benar
            'file_put_contents',
            'fwrite', // jika digunakan untuk menulis ke sistem file
            'mysql_query',
            'mysqli_query',
            'pg_query', // jika digunakan tanpa prepared statements
            // Pola berbahaya untuk ASP Classic backdoor
            'WSCRIPT.SHELL',
            'Scripting.FileSystemObject',
            'WScript.Shell',
            'cmd /c',
            'Server.CreateObject',
            'objShell.exec',
            'getCommandOutput',
            'ExecuteGlobal',
            'Execute',
            'Eval',
            'Run',
            'Shell.Application',
            'ShellExecute'
        ];

        $medium_risk = [
            'base64_decode',
            'gzinflate',
            'gzuncompress',
            'gzdecode',
            'str_rot13',
            'strrev',
            'preg_replace',
            'create_function',
            'include',
            'include_once',
            'require',
            'require_once',
            'curl_exec',
            'curl_multi_exec',
            'parse_ini_file',
            'show_source',
            'highlight_file',
            'fopen',
            'file_get_contents',
            'readfile',
            'unlink',
            'rmdir',
            'mkdir',
            'rename',
            'copy',
            'symlink',
            'mysql_connect',
            'mysqli_connect',
            'pg_connect', // koneksi database langsung
            'chmod',
            'chown',
            'chgrp', // perubahan izin file
            'ini_get',
            'phpinfo', // pengungkapan informasi
            'header', // jika digunakan untuk redirect mencurigakan
            'setcookie',
            'session_start',
            'session_destroy', // manipulasi sesi/cookie
        ];

        $low_risk = [
            'error_reporting',
            'set_time_limit',
            'ignore_user_abort',
            'define',
            'defined',
            'die',
            'exit',
            'print',
            'printf',
            'vprintf',
            'trigger_error',
            'debug_backtrace',
            'debug_print_backtrace',
            'var_dump',
            'print_r',
            'highlight_string',
            'token_get_all',
            'token_name',
            'get_defined_functions',
            'func_get_args',
            'func_get_arg',
            'func_num_args',
            'get_included_files',
            'get_required_files',
            'memory_get_usage',
            'memory_get_peak_usage',
            'register_shutdown_function',
            'register_tick_function',
        ];

        // Pola khusus yang sering digunakan dalam backdoor
        $special_patterns = [
            'r57shell',
            'c99shell',
            'shellbot',
            'phpshell',
            'void.ru',
            'phpremoteview',
            'directmail',
            'bash_history',
            'multiviews',
            'cwings',
            'vandal',
            'bitchx',
            'eggdrop',
            'guardservices',
            'psybnc',
            'zombie',
            'assasin',
            'sniffer',
            'xtreme',
            'spy',
            'rootkit',
            'hacktool',
            'hacktools',
            'hackers',
            'hacking',
            'php_backdoor',
            'backdoor',
            'malicious',
            'exploit',
            'exploits',
            'keylogger',
            'trojan',
            'trojanhorses',
            'worm',
            'virus',
            'bypass',
            'bypassed',
            'bypassing',
            'overwrite',
            'scan',
            'scanner',
            'xploit',
            'rootshell',
            'binder',
            'bindshell',
            'backconnect',
            'connect-back',
            'reverse shell',
            'reverse-shell',
            'revshell'
        ];

        if (in_array(strtolower($pattern), $high_risk))
            return 3;
        if (in_array(strtolower($pattern), $medium_risk))
            return 2;
        if (in_array(strtolower($pattern), $low_risk))
            return 1;
        if (in_array(strtolower($pattern), $special_patterns))
            return 4; // Skor tertinggi untuk pola khusus

        // Cek pola regex untuk string yang dienkode
        if (preg_match('/\\\\x[0-9A-Fa-f]{2}/', $pattern))
            return 3; // Hex-encoded string
        if (preg_match('/[a-zA-Z0-9+\/]{32,}={0,2}/', $pattern))
            return 3; // Possible base64

        return 0;
    }

    public function extract_zip($file_path, $extract_to)
    {
        $zip = new ZipArchive;
        if ($zip->open($file_path) === TRUE) {
            $zip->extractTo($extract_to);
            $zip->close();
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function check_fake_image_header($file_content)
    {
        $suspicious_headers = ['GIF89a;', 'GIF87a;'];
        foreach ($suspicious_headers as $header) {
            if (strpos($file_content, $header) === 0 && !$this->is_valid_image($file_content)) {
                return true;
            }
        }
        return false;
    }

    private function is_valid_image($file_content)
    {
        $image_info = @getimagesizefromstring($file_content);
        return $image_info !== false;
    }
}
?>