<?php
/**
 * Unit tests for SinceTagSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Tests\Commenting;

use NilambarCodingStandard\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for SinceTagSniff.
 */
final class SinceTagUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function getErrorList() {
		return array(
			5   => 1,
			11  => 1,
			16  => 1,
			25  => 1,
			34  => 1,
			41  => 1,
			49  => 1,
			94  => 1,
			95  => 1,
			104 => 1,
			116 => 1,
			122 => 1,
			131 => 1,
			139 => 1,
			147 => 1,
			155 => 1,
			163 => 1,
			181 => 1,
			306 => 1,
			397 => 1,
		);
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function getWarningList() {
		return array(
			68  => 1,
			77  => 1,
			172 => 1,
			233 => 1,
			240 => 1,
			241 => 1,
			315 => 1,
			367 => 1,
			378 => 1,
			388 => 1,
		);
	}

}
