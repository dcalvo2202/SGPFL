<?php
global $cds_domain;

function getEmailCSS(): string {
    return '
    <style>
        body { font-family: Arial, sans-serif; color: #333333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #fafafa; }
        .footer { margin-top: 25px; padding-top: 15px; border-top: 1px solid #ccc; font-size: 13px; color: #555; }
        .footer img { width: 120px; vertical-align: middle; margin-right: 10px; }
        .footer td { vertical-align: top; }
        .divider { border-left: 2px solid #999; width: 1px; }
        a { color: #0056b3; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .comments-box { background-color: #f0f0f0; border-left: 4px solid #0056b3; padding: 10px 15px; margin-top: 10px; }
    </style>';
}

function buildEmailFooter(string $date = ''): string {
    global $cds_domain;
    if (empty($date)) {
        $date = date("d/m/Y");
    }
    $logo_url = rtrim($cds_domain ?? '', '/') . '/base/img/logo.webp';
    return '
    <div class="footer">
        <table>
            <tr>
                <td><img src="' . $logo_url . '" alt="Escuela de Informática" style="width:120px;"></td>
                <td class="divider"></td>
                <td>
                    <strong>Escuela de Informática</strong><br>
                    Tel: <strong>(506) 2562-6363</strong> &nbsp;·&nbsp; Fax: <strong>(506) 2562-6384</strong><br>
                    <a href="mailto:escinf@una.cr">escinf@una.cr</a><br>
                    Universidad Nacional · Campus Presbítero Benjamín Núñez<br>
                    Heredia, Costa Rica
                </td>
            </tr>
        </table>
        <p style="margin-top:10px; font-size:12px; color:#777;">' . $date . '</p>
    </div>';
}

function wrapEmailBody(string $content, string $greeting = ''): string {
    $css = getEmailCSS();
    $footer = buildEmailFooter();
    $date_time = date("d/m/Y H:i");
    
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">' . $css . '
</head>
<body>
    <div class="container">
        ' . (!empty($greeting) ? '<p>' . $greeting . '</p>' : '') . '
        ' . $content . '
        ' . $footer . '
    </div>
</body>
</html>';
}