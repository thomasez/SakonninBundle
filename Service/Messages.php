<?php

namespace BisonLab\SakonninBundle\Service;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageContext;

/**
 * Messages service.
 */
class Messages
{

    private $container;
    private $entityManager;

    public function __construct($container)
    {
        $this->container         = $container;
    }

    public function postMessage($data, $context_data)
    {
        $em = $this->_getManager();
        $message = new Message($data);

        $em = $this->_getManager();
        if (isset($data['message_type']) && $message_type = $em->getRepository('BisonLabSakonninBundle:MessageType')->findOneByName($data['message_type'])) {
dump($message_type);
                $message->setMessageType($message_type);            
        } else {
            throw new \InvalidArgumentException("No message type found or set.");
        }

        if (isset($context_data)
            && isset($context_data['system'])
            && isset($context_data['object_name'])
            && isset($context_data['external_id'])) {

            $message_context = new MessageContext();
            $message->addContext($message_context);
            $message_context->setSystem($context_data['system']);
            $message_context->setObjectName($context_data['object_name']);
            $message_context->setExternalId($context_data['external_id']);
            $em->persist($message_context);
        }

        // Gotta find and a message type.

        $em->persist($message);
        $em->flush();

        return ($message);
    }

    private function _getManager()
    {
        if (!$this->entityManager) {
            $this->entityManager 
                = $this->container->get('doctrine')->getManager();
        }
        return $this->entityManager;
    } 
}
