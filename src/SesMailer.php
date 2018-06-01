<?php

namespace jakubenglicky\AWS;

use Aws\Ses\SesClient;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

class SesMailer implements IMailer
{
    /**
     * @var SesClient
     */
    private $sesClient;


    public function __construct(array $options)
    {
        if (!isset($options['version'])) {
            $options['version'] = 'latest';
        }

        if (!isset($options['region']) || empty($options['region'])) {
            throw new RegionException('Region must be selected.');
        }

        if (!isset($options['credentials'])) {
            throw new CredentialsException('Credentials must be set.');
        }

        $this->sesClient = new SesClient($options);
    }

    /**
     * @param Message $mail
     */
    function send(Message $mail)
    {
        $addresses = null;
        $i = 0;
        foreach ($mail->getHeader('To') as $item => $value) {
            $addresses[$i] = $item;
            $i = $i + 1;
        }

        $request = [];
        $request['Source'] = key($mail->getFrom());
        $request['Destination']['ToAddresses'] = $addresses;
        $request['Message']['Subject']['Data'] = $mail->getSubject();
        $request['Message']['Body']['Html']['Data'] = $mail->getHtmlBody();
        $request['Message']['Body']['Text']['Data'] = $mail->getBody();

        try {
           $this->sesClient->sendEmail($request);
        } catch (\Aws\Ses\Exception\SesException $e) {
            throw $e;
        }
    }
}
