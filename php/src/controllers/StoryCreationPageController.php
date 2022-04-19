<?php

namespace App\Controllers;

use App\Models\Story;
use PageController;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\SiteConfig\SiteConfig;

class StoryCreationPageController extends PageController
{
  private static $allowed_actions = [
    "StoryForm", "submitStory"
  ];

  private $successful = False;

  public function init()
  {
    $request = Injector::inst()->get(HTTPRequest::class);
    $session = $request->getSession();
    if ($session->get('StoryCreation') === 'Success') {
      $this->successful = True;
      $session->clear('StoryCreation');
    }
    return parent::init();
  }

  public function formSuccess()
  {
    return $this->successful;
  }

  public function StoryForm()
  {
    $fields = new FieldList (
      TextField::create("Name")
        ->addExtraClass('storyform-textfield'),
      EmailField::create("Email")
        ->addExtraClass('storyform-textfield'),
      TextField::create("PhoneNumber", 'Phone number')
        ->addExtraClass('storyform-textfield'),
      OptionsetField::create('StoryType', 'Story Type',[
        "Rescue" => "Rescue Story",
        "Fundraising" => "Fundraising Story",
        "Donor" => "Donor Story",
      ]),
      TextField::create('Title')
        ->addExtraClass('storyform-textfield'),
      DateField::create('Date')
        ->addExtraClass('storyform-date'),
      HTMLEditorField::create('Content')
        ->setDescription('Provide as much information as you’d like.')
        ->addExtraClass('storyform-story'),
      FileField::create('Image')
        ->addExtraClass('storyform-image'),
      TextField::create('VideoLink')
        ->addExtraClass('storyform-textfield'),
      HiddenField::create('Subsite')->setValue($this->Parent()->Theme)
    );
    $actions = new FieldList(
      FormAction::create("submitStory", "SUBMIT")
        ->addExtraClass('storyform-submit')
    );
    $requiredFields = new RequiredFields([
      'StoryType',
      'Name',
      'Email',
      'PhoneNumber',
      'Title',
      'Date'
    ]);

    $form = new Form($this, 'StoryForm', $fields, $actions, $requiredFields);
    $form->setTemplate('themes/app/templates/App/Forms/FormStoryCreation.ss');

    $form->enableSpamProtection();

    return $form;
  }

  public function submitStory($data, Form $form)
  {
    $submission = new Story();

    $form->saveInto($submission);
    $submission->write();

    $to = SiteConfig::current_site_config()->StoryEmail;
    $bcc = "forms@baa.co.nz";

    $email = Email::create();
    $email->setTo($to)
      ->setBCC($bcc)
      ->setSubject('Story Created')
      ->setData($submission)
      ->setHTMLTemplate('EmailStory');

    $email->send();

    $request = Injector::inst()->get(HTTPRequest::class);
    $session = $request->getSession();
    $session->set('StoryCreation', 'Success');

    return $this->redirect($this->Link());
  }
}