// Enhanced ESLint configuration for Vue.js code organization
module.exports = {
  root: true,
  extends: [
    "plugin:vue/vue3-essential",
    "eslint:recommended",
    "@vue/eslint-config-typescript/recommended",
    "@vue/eslint-config-prettier",
  ],
  parserOptions: {
    ecmaVersion: "latest",
  },
  rules: {
    // Existing rules
    "prettier/prettier": [
      "error",
      {
        semi: false,
      },
    ],

    // ==========================================
    // VUE ORGANIZATION RULES
    // ==========================================
    
    // Component naming
    'vue/component-definition-name-casing': ['error', 'PascalCase'],
    'vue/component-name-in-template-casing': ['error', 'PascalCase'],
    'vue/multi-word-component-names': 'error',
    
    // File size limits
    'vue/max-lines-per-file': ['error', {
      max: 150,
      skipBlankLines: true,
      skipComments: true
    }],
    
    // Template organization
    'vue/html-indent': ['error', 2],
    'vue/max-attributes-per-line': ['error', {
      singleline: 3,
      multiline: 1
    }],
    'vue/first-attribute-linebreak': ['error', {
      singleline: 'ignore',
      multiline: 'below'
    }],
    
    // Script organization (for Options API - if still used)
    'vue/order-in-components': ['error', {
      order: [
        'el',
        'name',
        'key',
        'parent',
        'functional',
        ['delimiters', 'comments'],
        ['components', 'directives', 'filters'],
        'extends',
        'mixins',
        ['provide', 'inject'],
        'ROUTER_GUARDS',
        'layout',
        'middleware',
        'validate',
        'scrollToTop',
        'transition',
        'loading',
        'inheritAttrs',
        'model',
        ['props', 'propsData'],
        'emits',
        'setup',
        'data',
        'computed',
        'watch',
        'LIFECYCLE_HOOKS',
        'methods',
        ['template', 'render'],
        'renderError'
      ]
    }],
    
    // Composition API organization
    'vue/define-props-declaration': ['error', 'type-based'],
    'vue/define-emits-declaration': ['error', 'type-based'],
    'vue/require-typed-ref': 'error',
    
    // Template best practices
    'vue/require-v-for-key': 'error',
    'vue/no-unused-vars': 'error',
    'vue/no-mutating-props': 'error',
    'vue/prefer-const-declaration': 'error',
    
    // ==========================================
    // TYPESCRIPT ORGANIZATION RULES
    // ==========================================
    '@typescript-eslint/no-explicit-any': 'warn',
    '@typescript-eslint/no-unused-vars': 'error',
    '@typescript-eslint/ban-ts-comment': 'error',
    '@typescript-eslint/ban-types': 'error',
    '@typescript-eslint/prefer-nullish-coalescing': 'error',
    '@typescript-eslint/prefer-optional-chain': 'error',
    
    // ==========================================
    // IMPORT ORGANIZATION RULES
    // ==========================================
    'import/order': ['error', {
      groups: [
        'builtin',
        'external',
        'internal',
        'parent',
        'sibling',
        'index'
      ],
      pathGroups: [
        {
          pattern: 'vue',
          group: 'external',
          position: 'before'
        },
        {
          pattern: 'vue-router',
          group: 'external',
          position: 'before'
        },
        {
          pattern: '@/**',
          group: 'internal',
          position: 'before'
        }
      ],
      pathGroupsExcludedImportTypes: ['builtin'],
      'newlines-between': 'always',
      alphabetize: {
        order: 'asc',
        caseInsensitive: true
      }
    }],
    
    // ==========================================
    // CODE QUALITY RULES
    // ==========================================
    'prefer-const': 'error',
    'no-var': 'error',
    'no-console': 'warn',
    'no-debugger': 'error'
  },
  
  overrides: [
    {
      files: ["cypress/e2e/**.{cy,spec}.{js,ts,jsx,tsx}"],
      extends: ["plugin:cypress/recommended"],
    },
    {
      files: ["**/*.{js,ts,vue}"],
      rules: {
        // Legacy overrides for gradual migration
        "@typescript-eslint/no-explicit-any": "off",
        "vue/multi-word-component-names": "off",
        "@typescript-eslint/no-unused-vars": "off",
      },
    },
    {
      // Stricter rules for new files
      files: ["src/features/**/*.vue", "src/components/**/*.vue"],
      rules: {
        "@typescript-eslint/no-explicit-any": "error",
        "vue/multi-word-component-names": "error",
        "@typescript-eslint/no-unused-vars": "error",
      },
    }
  ],
}