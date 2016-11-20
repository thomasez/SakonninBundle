<?php

namespace BisonLab\SakonninBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use BisonLab\SakonninBundle\Entity\Message;
use BisonLab\SakonninBundle\Entity\MessageType;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Message controller.
 *
 * @Route("/{access}/message", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class MessageController extends CommonController
{
    use \BisonLab\SakonninBundle\Lib\CommonStuff;

    /**
     * Lists all Message entities.
     *
     * @Route("/", name="message")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrineManager();
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $repo = $em->getRepository('BisonLabSakonninBundle:Message');
        $messages = $repo->createQueryBuilder('m')
            ->where('m.from = :userid')
            ->orWhere('m.to = :userid')
            ->setParameter('userid', $user->getId())
            ->getQuery()->getResult();
dump($messages);

        return $this->render('BisonLabSakonninBundle:Message:index.html.twig',
            array('entities' => $messages));
    }

    /**
     * Finds and displays a Message entity.
     *
     * @Route("/{id}", name="message_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Request $request, $access, $id)
    {
        $em = $this->getDoctrineManager();

        $entity = $em->getRepository('BisonLabSakonninBundle:Message')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Message entity.');
        }

        return array(
            'entity'      => $entity,
        );
    }

    /**
     * Lists all Messages with that context.
     *
     * @Route("/search_context/system/{system}/object_name/{object_name}/external_id/{external_id}", name="message_context_search")
     * @Method("GET")
     * @Template()
     */
    public function searchContextGetAction(Request $request, $access, $system, $object_name, $external_id)
    {
        $context_conf = $this->container->getParameter('app.contexts');
        $conf = $context_conf['BisonLabSakonninBundle']['Message'];
        $conf['entity'] = "BisonLabSakonninBundle:Message";
        $conf['show_template'] = "BisonLabSakonninBundle:Message:show.html.twig";
        $conf['list_template'] = "BisonLabSakonninBundle:Message:index.html.twig";
        return $this->contextGetAction(
                    $request, $conf, $access, $system, $object_name, $external_id);

    }

    /**
     * Creates a new PM
     *
     * @Route("/pm", name="pm_create")
     * @Method("POST")
     * @Template("BisonLabSakonninBundle:Message:new.html.twig")
     */
    public function createPmAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');

        $data = $request->request->all();
        $form = $sm->getCreatePmForm($data);
        $this->handleForm($form, $request, $access);

        if ($form->isValid()) {
            $message = $form->getData();
            $em = $this->getDoctrineManager();
            $message->setMessageType(
                $em->getRepository('BisonLabSakonninBundle:MessageType')
                    ->findOneByName('PM')
            );

            $message->setToType('INTERNAL');
            $message->setTo($data['to_userid']);

            $sm->postMessage($message);

            if ($this->isRest($access)) {
                return $this->returnRestData($request, $message);
            }
            return $this->redirect($this->generateUrl('message_show', array('id' => $message->getId())));
        }

        if ($this->isRest($access)) {
            # We have a problem, and need to tell our user what it is.
            # Better make this a Json some day.
            return $this->returnErrorResponse("Validation Error", 400, $this->handleFormErrors($form));
        }

        return array(
            'entity' => $message,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a new Message
     *
     * @Route("/", name="message_create")
     * @Method("POST")
     * @Template("BisonLabSakonninBundle:Message:new.html.twig")
     */
    public function createAction(Request $request, $access)
    {
        $sm = $this->container->get('sakonnin.messages');
        if ($data = json_decode($request->getContent(), true)) {
            $message = $sm->postMessage($data['message_data'], isset($data['message_context']) ? $data['message_context'] : array());
            if ($message) {
                return $this->returnRestData($request, $message->__toArray());
            }
            return $this->returnErrorResponse("Validation Error", 400);
        }

        $data = $request->request->all();
        $form = $sm->getCreateForm($data);
        $this->handleForm($form, $request, $access);

        if ($form->isValid()) {
            // Ok, it's valid. We'll send this to postMessage then.
            $message = $form->getData();
            if (!$message->getMessageType() && isset($data['message_type'])) {
                $em = $this->getDoctrineManager();
                $message->setMessageType(
                    $em->getRepository('BisonLabSakonninBundle:MessageType')
                        ->findOneByName($data['message_type'])
                );
            }

            $sm->postMessage($message);

            if ($this->isRest($access)) {
                return $this->returnRestData($request, $message);
            }
            return $this->redirect($this->generateUrl('message_show', array('id' => $message->getId())));
        }

        if ($this->isRest($access)) {
            # We have a problem, and need to tell our user what it is.
            # Better make this a Json some day.
            return $this->returnErrorResponse("Validation Error", 400, $this->handleFormErrors($form));
        }

        return array(
            'entity' => $message,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Message entity.
     *
     * @param MessageType $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    public function createCreateForm(Message $entity)
    {
        $form = $this->createForm(\BisonLab\SakonninBundle\Form\MessageType::class, $entity, array(
            'action' => $this->generateUrl('message_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Send'));

        return $form;
    }

    public function createCreatePmForm(Message $entity)
    {
        $form = $this->createForm(\BisonLab\SakonninBundle\Form\PmType::class, $entity, array(
            'action' => $this->generateUrl('pm_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Send'));

        return $form;
    }
}
