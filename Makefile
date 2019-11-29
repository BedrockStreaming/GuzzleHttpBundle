SHELL=bash
SOURCE_DIR = $(shell pwd)
BIN_DIR = ${SOURCE_DIR}/bin
COMPOSER = composer

define printSection
	@printf "\033[36m\n==================================================\n\033[0m"
	@printf "\033[36m $1 \033[0m"
	@printf "\033[36m\n==================================================\n\033[0m"
endef
define replaceDotenv
	test -f .env.dev.local || cp .env.dev .env.dev.local
	TEMPFILE="$(shell mktemp)" && \
	sed -e 's@^$1=.*@$1=$2@' .env.dev.local > "$$TEMPFILE" && \
	mv "$$TEMPFILE" .env.dev.local
endef

.PHONY: all
all: install quality test test-dependencies

.PHONY: ci
ci: quality test test-dependencies

.PHONY: install
install: clean-vendor composer-install

.PHONY: quality
quality: git-commit-checker cs-ci

.PHONY: test
test: atoum

.PHONY: test-dependencies
test-dependencies: sf-security-checker composer-source-checker

# Coding Style

.PHONY: cs
cs:
	${BIN_DIR}/php-cs-fixer fix --dry-run --stop-on-violation --diff

.PHONY: cs-fix
cs-fix:
	${BIN_DIR}/php-cs-fixer fix

.PHONY: cs-ci
cs-ci:
	${BIN_DIR}/php-cs-fixer fix --ansi --dry-run --using-cache=no --verbose

#COMPOSER

.PHONY: clean-vendor
clean-vendor:
	$(call printSection,CLEAN-VENDOR)
	rm -rf ${SOURCE_DIR}/vendor

.PHONY: composer-install
composer-install: ${SOURCE_DIR}/vendor/composer/installed.json

${SOURCE_DIR}/vendor/composer/installed.json:
	$(call printSection,COMPOSER INSTALL)
	$(COMPOSER) --no-interaction install --ansi --no-progress --prefer-dist
``````suggestion

# CI TOOLS

.PHONY: sf-security-checker
sf-security-checker: ${CI_DIR}
	$(call printSection,COMPOSER SECURITY CHECKER)
	${CI_DIR}/security-checker.phar security:check --ansi

.PHONY: composer-source-checker
composer-source-checker: ${SOURCE_DIR}/vendor/composer/installed.json ${CI_DIR}
	$(call printSection,COMPOSER SOURCE CHECKER)
	${CI_DIR}/composer-source-checker.sh ${SOURCE_DIR}/vendor/composer/installed.json

git-commit-checker: ${CI_DIR}
	$(call printSection, GIT COMMIT MESSAGES CHECKER)
	${CI_DIR}/git-commit-checker.sh

# Whatever you need in CI_DIR => download corresponding content on Git
${CI_DIR}:
	git clone --depth=1 https://github.m6web.fr/m6web/tool-php-ci.git ${CI_DIR}

# ENV VARS
.PHONY: env
env: env-diff env-update

# Waiting from https://github.com/Tekill/env-diff/pull/6 to be merged.
#.PHONY: env-clean
#env-clean: env-diff env-update-clean

.PHONY: env-diff
env-diff:
	${BIN_DIR}/env-diff diff

.PHONY: env-update
env-update:
	${BIN_DIR}/env-diff actualize

#.PHONY: env-update-clean
#env-update-clean:
#	${BIN_DIR}/env-diff actualize -r

# TEST
.PHONY: atoum
atoum:
	$(call printSection,TEST atoum)
	${BIN_DIR}/atoum
