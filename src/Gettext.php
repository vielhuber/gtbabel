<?php
namespace vielhuber\gtbabel;

use Gettext\Translations;
use Gettext\Translation;
use Gettext\Generator\PoGenerator;
use Gettext\Generator\MoGenerator;
use Gettext\Loader\PoLoader;

class Gettext
{
    function __construct(Data $data = null, Settings $settings = null)
    {
        $this->data = $data ?: new Data();
        $this->settings = $settings ?: new Settings();
    }

    function export($direct_output = true)
    {
        $export = [];
        $files = [];
        $filename_prefix_tmp = tempnam(sys_get_temp_dir(), 'gtbabel_');
        $filename_zip = $filename_prefix_tmp . '.zip';
        $translations = $this->data->getGroupedTranslationsFromDatabase()['data'];

        foreach ($this->settings->getSelectedLanguageCodes() as $lng_source__value) {
            $export[$lng_source__value]['template'] = Translations::create('gtbabel');
            foreach ($this->settings->getSelectedLanguageCodes() as $lng_target__value) {
                if ($lng_source__value === $lng_target__value) {
                    continue;
                }
                $export[$lng_source__value][$lng_target__value] = Translations::create('gtbabel');
            }
            foreach ($translations as $translations__value) {
                if ($translations__value['lng_source'] !== $lng_source__value) {
                    continue;
                }
                $translation = Translation::create(
                    $translations__value['context'],
                    $translations__value[$lng_source__value]
                );
                if ($translations__value['shared'] == 1) {
                    $translation->getExtractedComments()->add('shared');
                }
                $export[$lng_source__value]['template']->add($translation);
                foreach ($this->settings->getSelectedLanguageCodes() as $lng_target__value) {
                    if ($lng_source__value === $lng_target__value) {
                        continue;
                    }
                    if (@$translations__value[$lng_target__value] == '') {
                        continue;
                    }
                    $translation = Translation::create(
                        $translations__value['context'],
                        $translations__value[$lng_source__value]
                    );
                    foreach (
                        [
                            'added',
                            'checked',
                            'shared',
                            'discovered_last_time',
                            'discovered_last_url_orig',
                            'discovered_last_url',
                            'translated_by'
                        ]
                        as $cols__value
                    ) {
                        if (@$translations__value[$lng_target__value . '_' . $cols__value] != '') {
                            $translation
                                ->getExtractedComments()
                                ->add(
                                    $cols__value . ': ' . $translations__value[$lng_target__value . '_' . $cols__value]
                                );
                        }
                    }
                    $translation->translate($translations__value[$lng_target__value]);
                    $export[$lng_source__value][$lng_target__value]->add($translation);
                }
            }
        }

        foreach ($export as $export__key => $export__value) {
            foreach ($export__value as $export__value__key => $export__value__value) {
                if (count($export__value__value) === 0) {
                    continue;
                }
                $filename_po = $export__value__key . '.po' . ($export__value__key === 'template' ? 't' : '');
                $filename_mo = $export__value__key . '.mo';
                $poGenerator = new PoGenerator();
                $poGenerator->generateFile(
                    $export__value__value,
                    $filename_prefix_tmp . '_' . $export__key . '_' . $filename_po
                );
                clearstatcache();
                $files[] = [
                    $filename_prefix_tmp . '_' . $export__key . '_' . $filename_po,
                    $export__key . '/' . $filename_po
                ];
                if ($export__key !== 'template') {
                    $moGenerator = new MoGenerator();
                    $moGenerator->generateFile(
                        $export__value__value,
                        $filename_prefix_tmp . '_' . $export__key . '_' . $filename_mo
                    );
                    clearstatcache();
                    $files[] = [
                        $filename_prefix_tmp . '_' . $export__key . '_' . $filename_mo,
                        $export__key . '/' . $filename_mo
                    ];
                }
            }
        }

        if ($direct_output === false) {
            return array_map(function ($files__value) {
                return $files__value[0];
            }, $files);
        }

        $zip = new \ZipArchive();
        $zip->open($filename_zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($files as $files__value) {
            $zip->addFile($files__value[0], $files__value[1]);
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($filename_zip));
        header('Content-Disposition: attachment; filename="gtbabel-gettext-' . date('Y-m-d-H-i-s') . '.zip"');
        readfile($filename_zip);
        @unlink($filename_zip);
        die();
    }

    function import($filename, $lng_source, $lng_target)
    {
        $this->data->clearTable($lng_source, $lng_target);
        $loader = new PoLoader();
        $translations = $loader->loadFile($filename);
        foreach ($translations as $translations__value) {
            $str = $translations__value->getOriginal();
            $comments = $translations__value->getExtractedComments();
            $context = null;
            if ($translations__value->getContext() != '') {
                $context = $translations__value->getContext();
            }
            $trans = null;
            if ($translations__value->getTranslation() != '') {
                $trans = $translations__value->getTranslation();
            }

            foreach (
                [
                    'added',
                    'checked',
                    'shared',
                    'discovered_last_time',
                    'discovered_last_url_orig',
                    'discovered_last_url',
                    'translated_by'
                ]
                as $cols__value
            ) {
                ${$cols__value} = null;
                foreach ($comments as $comments__value) {
                    if (strpos($comments__value, $cols__value . ': ') !== false) {
                        ${$cols__value} = str_replace($cols__value . ': ', '', $comments__value);
                        break;
                    }
                }
            }
            $this->data->editTranslation(
                $str,
                $context,
                $lng_source,
                $lng_target,
                $trans,
                $checked,
                $shared,
                $added,
                $discovered_last_time,
                $discovered_last_url_orig,
                $discovered_last_url,
                $translated_by
            );
        }
    }
}
