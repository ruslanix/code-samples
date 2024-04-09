const testFilePatterns = {
	'unit': '**/tests/unit/**/*.test.ts',
	'integration': '**/tests/integration/**/*.test.ts'
};

module.exports = {
	globals: {
		'ts-jest': {
			tsConfig: 'tsconfig.json'
		}
	},
	setupFiles: [
		'./tests/setup-tests.ts'
	],
	moduleFileExtensions: [
		'ts',
		'js'
	],
	transform: {
		'^.+\\.(ts|tsx)$': 'ts-jest'
	},
	testMatch: [
		testFilePatterns[process.env.TEST_TYPE || 'unit']
	],
	testEnvironment: 'node'
};
