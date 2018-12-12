<?php declare(strict_types = 1);

namespace jakubenglicky\AWS\SES;

use Aws\Credentials\Credentials;
use Aws\Result;
use Aws\Ses\SesClient;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

final class SesMailer implements IMailer
{
	/**
	 * @var SesClient
	 */
	private $sesClient;

	/**
	 * @var string
	 */
	private $charset;

	/**
	 * SesMailer constructor.
	 * @param array $options
	 * @throws CredentialsException
	 * @throws RegionException
	 */
	public function __construct(array $options)
	{
		if (!isset($options['profile'])) {
			$options['profile'] = 'default';
		}

		if (!isset($options['version'])) {
			$options['version'] = 'latest';
		}

		if (!isset($options['region']) || empty($options['region'])) {
			throw new RegionException('Region must be selected.');
		}

		if (!isset($options['credentials']) || !isset($options['credentials']['key']) || !isset($options['credentials']['secret'])) {
			throw new CredentialsException('Credentials must be set.');
		}

		$credentials = new Credentials(
			$options['credentials']['key'],
			$options['credentials']['secret'],
			(isset($options['credentials']['token'])) ? $options['credentials']['token'] : NULL,
			(isset($options['credentials']['expires'])) ? $options['credentials']['expires'] : NULL
		);

		$options['credentials'] = $credentials;

		$this->setCharset(Charset::UTF_8);

		$this->sesClient = new SesClient($options);
	}

	/**
	 * Send e-mail via AWS SES
	 * @param Message $mail
	 * @throws AddressExceptions
	 * @return Result
	 */
	public function send(Message $mail): Result
	{
		$addresses = NULL;
		$to = $mail->getHeader('To');

		if (!$to) {
			throw new AddressExceptions('You must set recipients.');
		}

		foreach ($to as $email => $name) {
			$addresses[] = $email;
		}

		$request = [];
		$request['Source'] = key($mail->getFrom());
		$request['ReplyToAddresses'] = [
			$mail->getReturnPath(),
		];
		$request['Destination']['ToAddresses'] = $addresses;
		$request['Message']['Subject'] = [
			'Data' => ($mail->getSubject()) ? $mail->subject : '',
			'Charset' => $this->getCharset(),
			];
		$request['Message']['Body']['Html'] = [
			'Data' => $mail->getHtmlBody(),
			'Charset' => $this->getCharset(),
			];
		$request['Message']['Body']['Text'] = [
			'Data' => $mail->getBody(),
			'Charset' => $this->getCharset(),
			];

		try {
		   return $this->sesClient->sendEmail($request);
		} catch (\Aws\Ses\Exception\SesException $e) {
			throw $e;
		}
	}

	/**
	 * Set e-mail charset
	 * @param string $charset
	 */
	public function setCharset(string $charset): void
	{
		$this->charset = $charset;
	}

	/**
	 * Return charset
	 * @return string
	 */
	public function getCharset(): string
	{
		return $this->charset;
	}
}
