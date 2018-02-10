<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Controller\TimesheetControllerTrait;
use App\Entity\Timesheet;
use App\Form\TimesheetEditForm;
use App\Form\Toolbar\TimesheetAdminToolbarForm;
use App\Repository\Query\TimesheetQuery;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Controller used for manage timesheet entries in the admin part of the site.
 *
 * @Route("/team/timesheet")
 * @Security("has_role('ROLE_TEAMLEAD')")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class TimesheetController extends AbstractController
{
    use TimesheetControllerTrait;

    /**
     * TimesheetController constructor.
     * @param bool $durationOnly
     */
    public function __construct(bool $durationOnly)
    {
        $this->setDurationMode($durationOnly);
    }

    /**
     * This route shows all users timesheet entries.
     *
     * @Route("/", defaults={"page": 1}, name="admin_timesheet")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_timesheet_paginated")
     * @Method("GET")
     *
     * @param $page
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page, Request $request)
    {
        $query = new TimesheetQuery();
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TimesheetQuery $query */
            $query = $form->getData();
        }

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('admin/timesheet.html.twig', [
            'entries' => $entries,
            'page' => $page,
            'query' => $query,
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * The route to stop a running entry.
     *
     * @Route("/{id}/stop", name="admin_timesheet_stop")
     * @Method({"GET"})
     * @Security("is_granted('stop', entry)")
     *
     * @param Timesheet $entry
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stopAction(Timesheet $entry)
    {
        return $this->stop($entry, 'admin_timesheet');
    }

    /**
     * The route to edit an existing entry.
     *
     * @Route("/{id}/edit", name="admin_timesheet_edit")
     * @Method({"GET", "POST"})
     * @Security("is_granted('edit', entry)")
     *
     * @param Timesheet $entry
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Timesheet $entry, Request $request)
    {
        return $this->edit($entry, $request, 'admin_timesheet_paginated', 'admin/timesheet_edit.html.twig');
    }

    /**
     * The route to create a new entry by form.
     *
     * @Route("/create", name="admin_timesheet_create")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        return $this->create($request, 'admin_timesheet', 'admin/timesheet_edit.html.twig');
    }

    /**
     * The route to delete an existing entry.
     *
     * @Route("/{id}/delete", name="admin_timesheet_delete")
     * @Method({"GET", "POST"})
     * @Security("is_granted('delete', entry)")
     *
     * @param Timesheet $entry
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Timesheet $entry, Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($entry);
        $entityManager->flush();

        return $this->redirectToRoute('admin_timesheet_paginated', ['page' => $request->get('page')]);
    }

    /**
     * @param Timesheet $entry
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getCreateForm(Timesheet $entry)
    {
        return $this->createForm(TimesheetEditForm::class, $entry, [
            'action' => $this->generateUrl('admin_timesheet_create'),
            'method' => 'POST',
            'duration_only' => $this->isDurationOnlyMode(),
            'include_user' => true
        ]);
    }

    /**
     * @param Timesheet $entry
     * @param int $page
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getEditForm(Timesheet $entry, $page)
    {
        return $this->createForm(TimesheetEditForm::class, $entry, [
            'action' => $this->generateUrl('admin_timesheet_edit', [
                'id' => $entry->getId(),
                'page' => $page
            ]),
            'method' => 'POST',
            'duration_only' => $this->isDurationOnlyMode(),
            'include_user' => true
        ]);
    }

    /**
     * @param TimesheetQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(TimesheetQuery $query)
    {
        return $this->createForm(TimesheetAdminToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_timesheet', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }
}
