<?php

namespace Drupal\chat\Config;

class BotConfig
{
    const INITIAL_GREETING = <<<EOT
    お問い合わせありがとうございます。
    チャットBotです。
    しばらくお待ちいただいて、応答がない場合、席を外しております。
    その場合は、お手数ですが、メールアドレスまたは、お電話番号を入力して送信ください。
    確認次第、入力いただいた情報 宛にやり取りをさせていただきます。
    【受付時間】9：00～18：00（土日祝日を除く）
    EOT;
}
