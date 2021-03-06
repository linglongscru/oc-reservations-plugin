<?php namespace VojtaSvoboda\Reservations\Components;

use App;
use Cms\Classes\ComponentBase;
use Config;
use Exception;
use Flash;
use Illuminate\Support\Facades\Log;
use Input;
use Lang;
use October\Rain\Exception\ApplicationException;
use October\Rain\Exception\ValidationException;
use Redirect;
use Session;
use VojtaSvoboda\Reservations\Facades\ReservationsFacade;

/**
 * Reservation Form component.
 *
 * @package VojtaSvoboda\Reservations\Components
 */
class ReservationForm extends ComponentBase
{
    public function componentDetails()
	{
		return [
			'name' => 'vojtasvoboda.reservations::lang.reservationform.name',
			'description' => 'vojtasvoboda.reservations::lang.reservationform.description',
		];
	}

    /**
     * AJAX form submit handler.
     */
    public function onSubmit()
    {
        // check CSRF token
        if (Session::token() != Input::get('_token')) {
            throw new ApplicationException('Form session expired! Please refresh the page.');
        }

        /** @var ReservationsFacade $facade */
        $facade = App::make('vojtasvoboda.reservations.facade');
        $data = Input::all();
        $facade->storeReservation($data);
    }

    /**
     * Fallback for non-ajax POST request.
     */
	public function onRun()
	{
        /** @var ReservationsFacade $facade */
        $facade = App::make('vojtasvoboda.reservations.facade');

		$error = false;
		if (Input::get('submit')) {

            // check CSRF token
            if (Session::token() != Input::get('_token')) {
                $error = 'Form session expired! Please refresh the page.';

            } else {

                try {
                    $data = Input::all();
                    $facade->storeReservation($data);
                    $msg = Lang::get('vojtasvoboda.reservations::lang.reservationform.success');
                    Flash::success($msg);

                    return Redirect::to($this->page->url . '#' . $this->alias, 303);

                } catch(ValidationException $e) {
                    $error = $e->getMessage();

                } catch(ApplicationException $e) {
                    $error = $e->getMessage();

                } catch(Exception $e) {
                    Log::error($e->getMessage());
                    $error = 'We\'re sorry, but something went wrong and the page cannot be displayed.';
                }
            }
		}

		// inject assets
        $this->injectAssets();

		// load booked dates
        $dates = $facade->getReservedDates();

        // template data
		$this->page['sent'] = Flash::check();
		$this->page['post'] = $_POST;
		$this->page['error'] = $error;
        $this->page['dates'] = json_encode($dates);
	}

    /**
     * Inject components assets.
     */
	private function injectAssets()
    {
        $this->addCss('/plugins/vojtasvoboda/reservations/assets/vendor/pickadate/lib/compressed/themes/classic.css');
        $this->addCss('/plugins/vojtasvoboda/reservations/assets/vendor/pickadate/lib/compressed/themes/classic.date.css');
        $this->addCss('/plugins/vojtasvoboda/reservations/assets/vendor/pickadate/lib/compressed/themes/classic.time.css');
        $this->addJs('/plugins/vojtasvoboda/reservations/assets/vendor/pickadate/lib/compressed/picker.js');
        $this->addJs('/plugins/vojtasvoboda/reservations/assets/vendor/pickadate/lib/compressed/picker.date.js');
        $this->addJs('/plugins/vojtasvoboda/reservations/assets/vendor/pickadate/lib/compressed/picker.time.js');
        $this->addJs('/plugins/vojtasvoboda/reservations/assets/js/reservationform.js');
    }
}
