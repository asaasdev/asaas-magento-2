<?php
namespace Asaas\Magento2\Logger;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class Handler extends RotatingFileHandler
{
  public function __construct()
  {
    parent::__construct(BP . '/var/log/asaas_webhook.log', 30, Logger::DEBUG);
  }
}
