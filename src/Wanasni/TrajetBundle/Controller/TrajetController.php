<?php

namespace Wanasni\TrajetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\Date;
use Wanasni\TrajetBundle\Entity\Alert;
use Wanasni\TrajetBundle\Entity\Trajet;
use Wanasni\TrajetBundle\Form\AlertType;
use Wanasni\TrajetBundle\Form\TrajetRegulierType;
use Wanasni\TrajetBundle\Form\TrajetType;
use Wanasni\TrajetBundle\Form\TrajetUniqueType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Wanasni\TrajetBundle\Entity\Search;

class TrajetController extends Controller
{
    /**
     * @Route("/proposer-trajet", name="trajet_proposer_unique" )
     */
    public function ProposerAction()
    {

        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException();
        }

        $trajet = new Trajet();
        // On crée le formulaire grâce à la TrajetUniqueType
        $form = $this->createForm(new TrajetUniqueType($this->getUser()), $trajet);

        // On récupère la requête
        $request = $this->getRequest();

        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $trajet->setConducteur($this->getUser());
                $em->persist($trajet);
                $em->flush();

                $serviceAlert=$this->container->get('wanasni_trajet.alert_mailer');
                $serviceAlert->EnvoyerMailer($trajet);

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Trajet bien ajouté');

                return $this->redirect($this->generateUrl(
                    'trajet_show',
                    array('id' => $trajet->getId())
                ));

            }

        }

        return $this->render(':Trajet/Proposer:proposer_trajet_unique.html.twig',
            array(
                'form' => $form->createView(),
            ));

    }


    /**
     * @Route("/proposer-trajet-regulier", name="trajet_proposer_regulier" )
     */
    public function ProposerRegulierAction()
    {

        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException();
        }

        $trajet = new Trajet();
        // On crée le formulaire grâce à la TrajetType
        $form = $this->createForm(new TrajetRegulierType($this->getUser()), $trajet);

        // On récupère la requête
        $request = $this->getRequest();

        // On vérifie qu'elle est de type POST
        if ($request->getMethod() == 'POST') {

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $trajet->setConducteur($this->getUser());
                $em->persist($trajet);
                $em->flush();

                $serviceAlert=$this->container->get('wanasni_trajet.alert_mailer');
                $serviceAlert->EnvoyerMailer($trajet);

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', 'Trajet bien ajouté');

                return $this->redirect($this->generateUrl(
                    'trajet_show',
                    array('id' => $trajet->getId())
                ));

            }

        }

        return $this->render(':Trajet/Proposer:proposer_trajet_regulier.html.twig',
            array(
                'form' => $form->createView(),
            ));

    }

    /**
     *
     * @Route("/voir-trajet/{id}", name="trajet_show")
     * @ParamConverter("trajet", class="WanasniTrajetBundle:Trajet")
     */
    public function ShowAction(Trajet $trajet)
    {
        return $this->render(':Trajet/Gerer:voir_trajet.html.twig',
            array(
                'trajet' => $trajet,
            ));
    }


    /**
     * @Route("/rechercher-trajet", name="trajet_search")
     */

    public function SearchAction(Request $request)
    {

        $search = new Search();
        $alert=new Alert();

        if ($this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $alert->setEmail($this->getUser()->getEmail());
        }

        $Trajets = null;

        if ($request->getMethod() == 'POST') {
            $search->setOrigine($request->get('search_origine'));
            $search->setDestination($request->get('search_destination'));
            $date = $request->get('search_date');

            $search->setDate($date);

            $alert->setOrigine($search->getOrigine());
            $alert->setDestination($search->getDestination());
            $alert->setDate($date);

            $em = $this->getDoctrine()->getManager();
            $rep = $em->getRepository('WanasniTrajetBundle:Trajet');
            $Trajets = $rep->SearchByOrigineAndDestination($search->getOrigine(), $search->getDestination(), $search->getDate());
            // On définit un message flash
            //$this->get('session')->getFlashBag()->add('info', 'Trajet Trouver');

            $form=$this->createForm(new AlertType(),$alert);

            return $this->render(':Trajet/Gerer:rechercher_trajet.html.twig', array(
                'search'=>$search,
                'trajets'=>$Trajets,
                'form'=>$form->createView()
            ));

        }

        return $this->render(':Trajet/Gerer:rechercher_trajet.html.twig', array(
            'search'=>$search,
            'trajets'=>$Trajets,
            'form'=>null
        ));
    }


    /**
     * @Route("/add-alert", name="trajet_create_alert")
     */
    public function AlertAction(Request $request){

        $alert=new Alert();
        $form= $this->createForm(new AlertType(),$alert);
        if($request->getMethod()=="POST"){
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()) {
                $em=$this->getDoctrine()->getManager();
                $em->persist($alert);
                $em->flush();
                $this->get('session')->getFlashBag()->add('info','Alert create');
                return $this->redirect($this->generateUrl('trajet_search'));
            }
        }


        return new Response();

    }



    /**
     * @Route("/prix-optimal/{metre}", name="prix_optimal")
     */

    public function PrixOptimalAction($metre)
    {
        // 100 km => 5 TND

        $prix = 0;

        $em = $this->getDoctrine()->getManager();
        $PrixOptimel = $em->find('WanasniTrajetBundle:PrixOptimal', 1);

        if ($PrixOptimel) {
            $prix = $metre / $PrixOptimel->getX();
        }

        return new JsonResponse(array('PrixOptimal' => ceil($prix), 'Unite' => 'TND'));
    }





    /**
     * @Route("/modifier-trajet/{id}", name="trajet_edit")
     * @ParamConverter("trajet", class="WanasniTrajetBundle:Trajet")
     */

    public function EditAction(Trajet $trajet){


        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }

        if($trajet->getFrequence()=="UNIQUE"){
            $form=$this->createForm(new TrajetUniqueType($this->getUser()), $trajet);
            return $this->render(':Trajet/Gerer:modifier_trajet_unique.html.twig',array(
                'form'=>$form->createView()
            ));
        }else{
            $form=$this->createForm(new TrajetRegulierType($this->getUser()), $trajet);
            return $this->render(':Trajet/Gerer:modifier_trajet_regulier.html.twig', array(
                'form'=>$form->createView()
            ));
        }



    }



}
