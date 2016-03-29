<?php

namespace Wanasni\TrajetBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SegmentType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('distance','text',array(
                'attr'=>array('class'=>'distance form-control'),
                'data'=>'0 km',
                'disabled'=>true,
            ))
            ->add('duration','text',array(
                'attr'=>array('class'=>'duration form-control'),
                'data'=>'0 heurs 0 min',
                'disabled'=>true,
            ))

            ->add('prix','number',array(
                'attr'=>array('class'=>'prix form-control')
            ))
            ->add('order','hidden',array(
                'attr'=>array('class'=>'order form-control'),
            ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Wanasni\TrajetBundle\Entity\Segment'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'wanasni_trajetbundle_segment';
    }
}
