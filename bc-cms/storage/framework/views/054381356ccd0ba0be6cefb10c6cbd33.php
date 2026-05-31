<div class="table-responsive">
	<table class="table table-striped table-inverse mb-1">
		<tbody>
		<?php
		$rows = array_values($rows);
		$from = $rows[0]['from'];
		$array = [];
		foreach ($rows as $item => $value) {
			if($item==0){
				$from = $value['from'];
				$array[$from] = $value;
			}
			if (!empty($rows[($item - 1)]['price']) and $value['price'] == $rows[($item - 1)]['price'] ) {
//				co ngay tiep theo va gia bang nhau
				$array[$from]['to'] = $value['from'];
				$array[$from]['to_html'] = $value['from_html'];
			} elseif(!empty($rows[($item - 1)]['price']) and $value['price'] != $rows[($item - 1)]['price'] ) {
				$from = $value['from'];
				$array[$from] = $value;
				$array[$from]['to'] = $value['from'];
				$array[$from]['to_html'] = $value['from_html'];
			}else{
				$array[$from]['to'] = $value['to'];
				$array[$from]['to_html'] = $value['to_html'];
			}
		}
		;?>
		<?php $__currentLoopData = $array; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
			<?php
			$days = max(1,floor(($value['to'] - $value['from']) / DAY_IN_SECONDS)+1);
			?>
			<tr>
				<td><?php echo e($value['from_html']); ?> <i class="fa fa-long-arrow-right"></i> <?php echo e($value['to_html']); ?></td>
				<td class="text-right"><?php echo e(format_money($value['price']*$number)); ?>*<?php echo e($days); ?></td>
			</tr>
		<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
		</tbody>
	</table>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Event/Views/frontend/booking/detail-date.blade.php ENDPATH**/ ?>