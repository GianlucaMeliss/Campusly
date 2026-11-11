<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppVersion;
use App\Core\Http;

class ApiController
{
    public function getAppVersion(): void
    {
        Http::json(
            ['version' => AppVersion::current()],
            200,
            ['Cache-Control' => 'no-store, max-age=0']
        );
    }

    // Metodi mantenuti per retrocompatibilità diretta (delegano ai controller dedicati)
    public function sendContact(): void { (new ContactController())->sendContact(); }
    public function logCookie(): void { (new ContactController())->logCookie(); }
    public function getCalendarEvents(): void { (new CalendarApiController())->getCalendarEvents(); }
    public function getPersonalEvents(): void { (new CalendarApiController())->getPersonalEvents(); }
    public function savePersonalEvent(): void { (new CalendarApiController())->savePersonalEvent(); }
    public function deletePersonalEvent(string $id): void { (new CalendarApiController())->deletePersonalEvent($id); }
    public function getHiddenCourses(): void { (new CalendarApiController())->getHiddenCourses(); }
    public function toggleHiddenCourse(): void { (new CalendarApiController())->toggleHiddenCourse(); }
    public function getCoursesByUniversity(string $uniId): void { (new OnboardingApiController())->getCoursesByUniversity($uniId); }
    public function getCourseCurriculums(string $courseId): void { (new OnboardingApiController())->getCourseCurriculums($courseId); }
    public function submitOnboardingRequest(): void { (new OnboardingApiController())->submitOnboardingRequest(); }
    public function createGroup(): void { (new GroupApiController())->createGroup(); }
    public function joinGroup(): void { (new GroupApiController())->joinGroup(); }
    public function leaveGroup(): void { (new GroupApiController())->leaveGroup(); }
    public function updateGroupPrivacy(): void { (new GroupApiController())->updateGroupPrivacy(); }
    public function getGroupCalendar(string $groupId): void { (new GroupApiController())->getGroupCalendar($groupId); }
}
