<?php

namespace BisonLab\SakonninBundle\Lib\Sakonnin;

use BisonLab\SakonninBundle\Entity\Message;

/*
 */

class NotifyiOnReceptionMailOnErrorSubject
{
    use CommonFunctions;

    public function execute($options = array())
    {
        $message = $options['message'];

        // Find who to send this to.
        $first = $message->getFirstPost();

        $receivers = isset($options['attributes']) 
            ? $options['attributes'] : array();

        $receivers[] = $first->getFrom();

        $pm = 'You got a message<br/>Subject: ' . $message->getSubject();

        $url = $this->router->generate('message_show',
            array('meessage_id' => $message->getMessageId()), true);
        $pm .= '<br/><a href="' . $url . '">Link to the message</a>';

        foreach ($receivers as $receiver) {
            $this->sendNotification($receiver, $pm, array('content_type' => 'text/html'));
        }

        // Then, check subject, no error, no mail.
        if (!preg_match("/error/i", $message->getSubject())) return true;

        // Find who to send this to.
        $first = $message->getFirstPost();

        $receivers = isset($options['attributes']) 
            ? $options['attributes'] : array();

        // I'm not ready for validating a mail address. this is just a simple.
        if ($first->getFrom())
            $receivers[] = $first->getFrom();

        $options['provide_link'] = true;
        foreach ($receivers as $receiver) {
            if ($email = $this->extractEmailFromReceiver($receiver))
                $this->sendMail($message, $email, $options);
        }
    }
}
