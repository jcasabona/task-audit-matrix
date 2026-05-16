<?php
/**
 * Plugin Name: Task Audit Matrix
 * Description: A four-quadrant Task Audit Matrix (Planned/Reactive × Focus/Process). Use the [task_audit_matrix] shortcode on any page. Combine with WordPress's built-in page password protection to gate access.
 * Version:     1.0.0
 * Author:      Joe Casabona
 * License:     GPL-2.0-or-later
 * Text Domain: task-audit-matrix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tam_render_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'height'     => '80vh',
			'storage_key' => 'task-audit-matrix:v1',
		),
		$atts,
		'task_audit_matrix'
	);

	$height      = preg_replace( '/[^0-9a-z%.\-]/i', '', $atts['height'] );
	$storage_key = sanitize_text_field( $atts['storage_key'] );

	ob_start();
	?>
	<div class="tam-root" style="--tam-height: <?php echo esc_attr( $height ); ?>;" data-storage-key="<?php echo esc_attr( $storage_key ); ?>">
		<style>
			.tam-root {
				--tam-bg: #F5EFE0;
				--tam-navy: #14304A;
				--tam-yellow: #E8C463;
				--tam-muted: #6B6356;
				background: var(--tam-bg);
				color: var(--tam-navy);
				font-family: Georgia, "Times New Roman", serif;
				padding: 12px 20px 16px;
				border-radius: 10px;
				display: flex;
				flex-direction: column;
				min-height: var(--tam-height);
				box-sizing: border-box;
			}
			.tam-root *, .tam-root *::before, .tam-root *::after { box-sizing: border-box; }

			.tam-root .tam-title {
				flex: 0 0 auto;
				text-align: center;
				font-size: clamp(24px, 3.2vw, 40px);
				font-weight: 700;
				letter-spacing: -0.5px;
				margin: 8px 0 4px;
				color: var(--tam-navy);
				font-family: Georgia, "Times New Roman", serif;
			}

			.tam-root .tam-panel {
				flex: 0 0 auto;
				background: #fff;
				border: 1px solid #E2D9C2;
				border-radius: 10px;
				padding: 10px 14px;
				box-shadow: 0 1px 0 rgba(0,0,0,0.03);
				font-family: system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
				max-width: 1200px;
				width: 100%;
				margin: 16px auto;
			}
			.tam-root .tam-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
			.tam-root .tam-row input[type=text] {
				flex: 1 1 280px;
				min-width: 180px;
				padding: 8px 11px;
				border: 1px solid #C8BFA8;
				border-radius: 6px;
				font-size: 14px;
				font-family: inherit;
				background: #FBF8F0;
				color: var(--tam-navy);
			}
			.tam-root .tam-row select {
				padding: 8px 11px;
				border: 1px solid #C8BFA8;
				border-radius: 6px;
				font-size: 14px;
				font-family: inherit;
				background: #FBF8F0;
				color: var(--tam-navy);
			}
			.tam-root .tam-row button {
				padding: 8px 16px;
				border: none;
				border-radius: 6px;
				background: var(--tam-navy);
				color: #fff;
				font-size: 14px;
				font-weight: 600;
				cursor: pointer;
				font-family: inherit;
			}
			.tam-root .tam-row button.tam-secondary { background: #fff; color: var(--tam-navy); border: 1px solid #C8BFA8; }
			.tam-root .tam-row button:hover { opacity: 0.92; }
			.tam-root .tam-hint { color: var(--tam-muted); font-size: 12px; margin-top: 4px; font-family: system-ui, sans-serif; }

			.tam-root .tam-matrix {
				flex: 1 1 auto;
				position: relative;
				width: 100%;
				max-width: 1400px;
				margin: 0 auto;
				min-height: 320px;
			}

			.tam-root .tam-axis-label {
				position: absolute;
				font-family: Georgia, serif;
				font-weight: 700;
				font-size: clamp(10px, 1vw, 14px);
				letter-spacing: 3px;
				color: var(--tam-navy);
				text-transform: uppercase;
			}
			.tam-root .tam-axis-label.tam-planned  { top: 4%;  left: 18%; }
			.tam-root .tam-axis-label.tam-reactive { top: 4%;  right: 18%; }
			.tam-root .tam-axis-label.tam-focus    { left: 0;  top: 22%;  writing-mode: vertical-rl; transform: rotate(180deg); }
			.tam-root .tam-axis-label.tam-process  { left: 0;  bottom: 8%; writing-mode: vertical-rl; transform: rotate(180deg); }

			.tam-root .tam-cross { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }

			.tam-root .tam-quad {
				position: absolute;
				width: 50%;
				height: 50%;
				padding: 5% 4% 3%;
				overflow: hidden;
			}
			.tam-root .tam-quad.tam-q1 { top: 0;    left: 0;    text-align: left;   padding-left: 7%; padding-top: 5%; }
			.tam-root .tam-quad.tam-q2 { top: 0;    right: 0;   text-align: right;  padding-right: 7%; padding-top: 5%; }
			.tam-root .tam-quad.tam-q3 { bottom: 0; left: 0;    text-align: left;   padding-left: 7%; padding-top: 3%; }
			.tam-root .tam-quad.tam-q4 { bottom: 0; right: 0;   text-align: right;  padding-right: 7%; padding-top: 3%; }

			.tam-root .tam-task {
				display: block;
				font-family: Georgia, serif;
				font-size: clamp(12px, 1.3vw, 18px);
				color: var(--tam-navy);
				margin: 0.25em 0;
				line-height: 1.2;
				cursor: pointer;
				position: relative;
			}
			.tam-root .tam-task .tam-x {
				opacity: 0;
				margin-left: 8px;
				color: #B33;
				font-size: 0.8em;
				font-family: system-ui, sans-serif;
			}
			.tam-root .tam-task:hover .tam-x { opacity: 1; }

			.tam-root .tam-quad.tam-q1 .tam-task:nth-child(2) { margin-left:  16px; }
			.tam-root .tam-quad.tam-q1 .tam-task:nth-child(3) { margin-left:  32px; }
			.tam-root .tam-quad.tam-q1 .tam-task:nth-child(4) { margin-left:  48px; }
			.tam-root .tam-quad.tam-q1 .tam-task:nth-child(5) { margin-left:  64px; }
			.tam-root .tam-quad.tam-q1 .tam-task:nth-child(6) { margin-left:  80px; }
			.tam-root .tam-quad.tam-q1 .tam-task:nth-child(7) { margin-left:  96px; }
			.tam-root .tam-quad.tam-q1 .tam-task:nth-child(n+8) { margin-left: 112px; }

			.tam-root .tam-quad.tam-q2 .tam-task:nth-child(2) { margin-right:  16px; }
			.tam-root .tam-quad.tam-q2 .tam-task:nth-child(3) { margin-right:  32px; }
			.tam-root .tam-quad.tam-q2 .tam-task:nth-child(4) { margin-right:  48px; }
			.tam-root .tam-quad.tam-q2 .tam-task:nth-child(5) { margin-right:  64px; }
			.tam-root .tam-quad.tam-q2 .tam-task:nth-child(6) { margin-right:  80px; }
			.tam-root .tam-quad.tam-q2 .tam-task:nth-child(7) { margin-right:  96px; }
			.tam-root .tam-quad.tam-q2 .tam-task:nth-child(n+8) { margin-right: 112px; }

			.tam-root .tam-quad.tam-q3 .tam-task:nth-child(1) { margin-left:  48px; }
			.tam-root .tam-quad.tam-q3 .tam-task:nth-child(2) { margin-left:  32px; }
			.tam-root .tam-quad.tam-q3 .tam-task:nth-child(3) { margin-left:  16px; }
			.tam-root .tam-quad.tam-q3 .tam-task:nth-child(n+4) { margin-left: 0; }

			.tam-root .tam-quad.tam-q4 .tam-task:nth-child(1) { margin-right:  48px; }
			.tam-root .tam-quad.tam-q4 .tam-task:nth-child(2) { margin-right:  32px; }
			.tam-root .tam-quad.tam-q4 .tam-task:nth-child(3) { margin-right:  16px; }
			.tam-root .tam-quad.tam-q4 .tam-task:nth-child(n+4) { margin-right: 0; }

			@media print {
				.tam-root .tam-panel, .tam-root .tam-hint { display: none !important; }
				.tam-root { min-height: auto; }
				.tam-root .tam-matrix { height: 80vh; }
			}
		</style>

		<h1 class="tam-title">Task Audit Matrix</h1>

		<div class="tam-panel">
			<form class="tam-row tam-form" autocomplete="off">
				<input type="text" class="tam-name" placeholder="Add a task (e.g., Record podcast)" required>
				<select class="tam-type" required>
					<option value="Planned">Planned</option>
					<option value="Reactive">Reactive</option>
				</select>
				<select class="tam-mode" required>
					<option value="Focus">Focus</option>
					<option value="Process">Process</option>
				</select>
				<button type="submit">Add</button>
				<button type="button" class="tam-secondary tam-print">Print</button>
				<button type="button" class="tam-secondary tam-clear">Clear all</button>
			</form>
			<div class="tam-hint">Click a task on the matrix to remove it. Entries save in this browser only.</div>
		</div>

		<div class="tam-matrix">
			<svg class="tam-cross" viewBox="0 0 1000 600" preserveAspectRatio="none" aria-hidden="true">
				<path d="M 500 30 C 502 150, 498 300, 501 420 C 503 480, 499 540, 500 580"
				      stroke="#E8C463" stroke-width="6" fill="none" stroke-linecap="round" opacity="0.95" />
				<path d="M 30 300 C 180 302, 360 298, 540 301 C 720 303, 880 299, 970 300"
				      stroke="#E8C463" stroke-width="6" fill="none" stroke-linecap="round" opacity="0.95" />
			</svg>

			<div class="tam-axis-label tam-planned">Planned</div>
			<div class="tam-axis-label tam-reactive">Reactive</div>
			<div class="tam-axis-label tam-focus">Focus</div>
			<div class="tam-axis-label tam-process">Process</div>

			<div class="tam-quad tam-q1"></div>
			<div class="tam-quad tam-q2"></div>
			<div class="tam-quad tam-q3"></div>
			<div class="tam-quad tam-q4"></div>
		</div>

		<script>
		(function () {
			var roots = document.querySelectorAll('.tam-root:not([data-tam-init])');
			roots.forEach(function (root) {
				root.setAttribute('data-tam-init', '1');
				var STORE_KEY = root.getAttribute('data-storage-key') || 'task-audit-matrix:v1';

				var form  = root.querySelector('.tam-form');
				var name  = root.querySelector('.tam-name');
				var type  = root.querySelector('.tam-type');
				var mode  = root.querySelector('.tam-mode');
				var print = root.querySelector('.tam-print');
				var clear = root.querySelector('.tam-clear');
				var quads = {
					q1: root.querySelector('.tam-q1'),
					q2: root.querySelector('.tam-q2'),
					q3: root.querySelector('.tam-q3'),
					q4: root.querySelector('.tam-q4')
				};

				function load() {
					try { return JSON.parse(localStorage.getItem(STORE_KEY) || '[]'); }
					catch (_) { return []; }
				}
				function save(tasks) {
					try { localStorage.setItem(STORE_KEY, JSON.stringify(tasks)); } catch (_) {}
				}
				function quadrantOf(t) {
					if (t.type === 'Planned'  && t.mode === 'Focus')   return quads.q1;
					if (t.type === 'Reactive' && t.mode === 'Focus')   return quads.q2;
					if (t.type === 'Planned'  && t.mode === 'Process') return quads.q3;
					return quads.q4;
				}
				function render() {
					Object.keys(quads).forEach(function (k) { quads[k].innerHTML = ''; });
					var tasks = load();
					tasks.forEach(function (t, idx) {
						var div = document.createElement('div');
						div.className = 'tam-task';
						div.textContent = t.name;
						var x = document.createElement('span');
						x.className = 'tam-x';
						x.textContent = '×';
						div.appendChild(x);
						div.addEventListener('click', function () {
							var current = load();
							current.splice(idx, 1);
							save(current);
							render();
						});
						quadrantOf(t).appendChild(div);
					});
				}

				form.addEventListener('submit', function (e) {
					e.preventDefault();
					var n = name.value.trim();
					if (!n) return;
					var tasks = load();
					tasks.push({ name: n, type: type.value, mode: mode.value });
					save(tasks);
					name.value = '';
					name.focus();
					render();
				});

				clear.addEventListener('click', function () {
					if (confirm('Remove all tasks from this matrix?')) {
						save([]);
						render();
					}
				});

				print.addEventListener('click', function () { window.print(); });

				render();
			});
		})();
		</script>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'task_audit_matrix', 'tam_render_shortcode' );
