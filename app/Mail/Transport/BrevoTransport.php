<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Facades\Http;
use Swift_Mime_Attachment;
use Swift_Mime_SimpleMessage;
use Swift_Mime_SimpleMimeEntity;
use Swift_TransportException;

class BrevoTransport extends Transport
{
    private $key;

    public function __construct(string $key)
    {
        $this->key = trim($key);
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        if ($this->key === '') {
            throw new Swift_TransportException('Brevo mail requires BREVO_API_KEY.');
        }

        $this->beforeSendPerformed($message);
        $payload = $this->payload($message);

        try {
            // Never redirect the API key to another host or retry an ambiguous send.
            $response = Http::withHeaders(['api-key' => $this->key])
                ->acceptJson()
                ->withOptions(['connect_timeout' => 5, 'allow_redirects' => false])
                ->timeout(15)
                ->post('https://api.brevo.com/v3/smtp/email', $payload);
        } catch (ConnectionException $exception) {
            // Request exceptions can contain API keys and password-reset links.
            throw new Swift_TransportException('Unable to connect to Brevo. Check network access and Brevo transactional logs before retrying.');
        }

        if (!$response->successful()) {
            throw new Swift_TransportException('Brevo rejected the email (HTTP ' . $response->status() . '). Check the API key, verified sender, account activation and sending quota.', $response->status());
        }

        $messageId = $response->json('messageId');
        if (!is_string($messageId) || trim($messageId) === '' || strpbrk($messageId, "\r\n") !== false) {
            throw new Swift_TransportException('Brevo returned no valid messageId. Check Brevo transactional logs before retrying.');
        }

        $message->getHeaders()->addTextHeader('X-Brevo-Message-ID', $messageId);
        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    private function payload(Swift_Mime_SimpleMessage $message): array
    {
        $senders = $this->addresses((array) $message->getFrom());
        $to = $this->addresses((array) $message->getTo());
        if (count($senders) !== 1 || !$to) {
            throw new Swift_TransportException('Brevo requires one sender and at least one To recipient.');
        }

        $payload = [
            'sender' => $senders[0],
            'to' => $to,
            'subject' => (string) $message->getSubject(),
        ];
        foreach (['cc' => $message->getCc(), 'bcc' => $message->getBcc()] as $field => $addresses) {
            if ($addresses) {
                $payload[$field] = $this->addresses($addresses);
            }
        }
        $replyTo = $this->addresses((array) $message->getReplyTo());
        if (count($replyTo) > 1) {
            throw new Swift_TransportException('Brevo supports only one Reply-To address.');
        }
        if ($replyTo) {
            $payload['replyTo'] = $replyTo[0];
        }

        $this->addContent($message, $payload);
        if (!isset($payload['htmlContent']) && !isset($payload['textContent'])) {
            throw new Swift_TransportException('Brevo requires an HTML or plain text email body.');
        }

        return $payload;
    }

    private function addresses(array $addresses): array
    {
        $result = [];
        foreach ($addresses as $email => $name) {
            $recipient = ['email' => $email];
            if ($name !== null && $name !== '') {
                $recipient['name'] = $name;
            }
            $result[] = $recipient;
        }

        return $result;
    }

    private function addContent(Swift_Mime_SimpleMimeEntity $part, array &$payload): void
    {
        if ($part instanceof Swift_Mime_Attachment) {
            if ($part->getDisposition() !== 'attachment') {
                throw new Swift_TransportException('Inline email attachments are not supported by the Brevo transport.');
            }
            $payload['attachment'][] = [
                'name' => $part->getFilename(),
                'content' => base64_encode((string) $part->getBody()),
            ];
            return;
        }

        // Swift keeps the original body type separately when adding MIME alternatives.
        $field = ['text/html' => 'htmlContent', 'text/plain' => 'textContent'][$part->getBodyContentType()] ?? null;
        if ($field && $part->getBody() !== null) {
            $payload[$field] = (string) $part->getBody();
        }
        foreach ($part->getChildren() as $child) {
            $this->addContent($child, $payload);
        }
    }
}
