/* Belarusian keyboard layouts
 * contains layout: 'bulgarian-qwerty'
 *
 * To use:
 *  Point to this js file into your page header: <script src="layouts/belarusian.js" type="text/javascript"></script>
 *  Initialize the keyboard using: $('input').keyboard({ layout: 'bulgarian-qwerty' });
 *
 * license for this file: WTFPL, unless the source layout site has a problem with me using them as a reference
 */

/* from http://ascii-table.com/keyboard.php/442 */
jQuery.keyboard.layouts['bulgarian-qwerty'] = {
	'name' : 'bulgarian-qwerty',
	'lang' : ['bg'],
	'normal' : [
		"` 1 2 3 4 5 6 7 8 9 0 - = {bksp}",
		"{tab} q w e r t y u i o p [ ] \\",
		"a s d f g h j k l ; ' {enter}",
		"{shift} z x c v b n m , . / {shift}",
		"{accept} {alt} {meta1} {space} {alt} {cancel}"
	],
	'shift' : [
		'~ ! @ # $ % ^ & * ( ) _ + {bksp}',
		"{tab} Q W E R T Y U I O P { } |",
		'A S D F G H J K L : " {enter}',
		"{shift} Z X C V B N M < > ? {shift}",
		"{accept} {alt} {meta1} {space} {alt} {cancel}"
	],
	'alt' : [
		'` 1 2 3 4 5 6 7 8 9 0 - . {bksp}',
		"{tab} , \u0443 \u0435 \u0438 \u0448 \u0449 \u043a \u0441 \u0434 \u0437 \u0446 ; (",
		"\u044c \u044f \u0430 \u043e \u0436 \u0433 \u0442 \u043d \u0432 \u043c \u0447 {enter}",
		"{shift} \u044e \u0439 \u044a \u044d \u0444 \u0445 \u043f \u0440 \u043b \u0431 {shift}",
		"{accept} {alt} {meta1} {space} {alt} {cancel}"
	],
	'alt-shift' : [
		'~ ! ? + " % = : / _ \u2116 I V {bksp}',
		"{tab} \u044b \u0423 \u0415 \u0418 \u0428 \u0429 \u041a \u0421 \u0414 \u0417 \u0426 \u00a7 )",
		"\u042c \u042f \u0410 \u041e \u0416 \u0413 \u0422 \u041d \u0412 \u041c \u0427 {enter}",
		"{shift} \u042e \u0419 \u042a \u042d \u0424 \u0425 \u041f \u0420 \u041b \u0411 {shift}",
		"{accept} {alt} {meta1} {space} {alt} {cancel}"
	],
	'meta1' :  [
		"\u0447 1 2 3 4 5 6 7 8 9 0 - = {bksp}",
		"{tab} \u044f \u0432 \u0435 \u0440 \u0442 \u044a \u0443 \u0438 \u043e \u043f \u0448 \u0449 \u044e",
		"\u0430 \u0441 \u0434 \u0444 \u0433 \u0445 \u0439 \u043a \u043b ; ' {enter}",
		"{shift} \u044e \u0437 \u044c \u0446 \u0436 \u0431 \u043d \u043c , . / {shift}",
		"{accept} {alt}  {meta1} {space} {alt} {cancel}"
	],
	'meta1-shift' : [
		"\u0427 ! @ \u2116 $ % \u20ac \u00a7 * ( ) _ + {bksp}",
		"{tab} \u042f \u0412 \u0415 \u0420 \u0422 \u042a \u0423 \u0418 \u041e \u041f \u0428 \u0429 \u042e",
		"\u0410 \u0421 \u0414 \u0424 \u0413 \u0425 \u0419 \u041a \u041b : \" {enter}",
		"{shift} \u042e \u0417 \u045d \u0426 \u0416 \u0411 \u041d \u041c < > / {shift}",
		"{accept} {alt} {meta1} {space} {alt} {cancel}"
	]

};
