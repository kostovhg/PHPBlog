<?php
/**
 * Created by PhpStorm.
 * User: uKBo
 * Date: 11/8/2016
 * Time: 6:28 PM
 */

namespace SoftUniBlogBundle\Controller\Admin;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SoftUniBlogBundle\Entity\Role;
use SoftUniBlogBundle\Entity\User;
use SoftUniBlogBundle\Form\UserEditType;
use SoftUniBlogBundle\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Route("/admin/users")
 * Class UserController
 * @package SoftUniBlogBundle\Controller\Admin
 */
class UserController extends Controller
{
    /**
     * @Route("/", name="admin_users")
     * @return \Symfony\Component\HttpFoundation\Response
     */

    public function listUsers()
    {
        $users =
            $this->getDoctrine()->getRepository(User::class)
                ->findAll();

        return $this->render("admin/user/all.html.twig",
            ['users'=> $users]);
    }

    /**
     * @Route("/edit/{id}", name="admin_user_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editUser($id, Request $request)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if($user === null){
            return $this->redirectToRoute("admin_users");
        }

        $originalPassword = $user->getPassword();

        $form = $this->createForm(UserEditType::class, $user);

        $form->handleRequest($request);

        if($form->isValid() && $form->isSubmitted()){

            // get form request about roles
            $rolesRequest = $user->getRoles();
            // get roles as strings from repository
            $roleRepository = $this->getDoctrine()->getRepository(Role::class);
            // prepare empty roles array for current user
            $roles = [];

            // fill roles array with roles value, according name of roles from repository
            foreach ($rolesRequest as $roleName){
                $roles[] = $roleRepository->findOneBy(['name' => $roleName]);
            }

            // set values of roles for the user
            $user->setRoles($roles);

            // Deal with password. If empty, set old hashed password
            // if not, encrypt new password and set it
            if (empty($user->getPassword())){
                $user->setPassword($originalPassword);
            } else{
                $password = $this->get('security.password_encoder')
                    ->encodePassword($user, $user->getPassword());
                $user->setPassword($password);
            }


            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user'=> $user,
            'form'=> $form->createView()
        ]);

    }

    /**
     * @Route("/delete/{id}", name="admin_user_delete")
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteUser($id, Request $request)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if($user === null){
            return $this->redirectToRoute("admin_users");
        }

        $form = $this->createForm(UserEditType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();
            // deleting all users articles
            foreach ($user->getArticles() as $article){
                $em->remove($article);
            }

            $em->remove($user);
            $em->flush();

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/delete.html.twig', ['user' => $user,
        'form' => $form->createView()
        ]);
    }
}