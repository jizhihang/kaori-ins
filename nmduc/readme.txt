HOW TO TRAIN DPM MODELS

1. Run prepare_annotation_for_dpm to generate training images (pos, neg) and annotations (bounding boxes) based on query images and masks.
prepare_annotation_for_dpm('tv2013', 'query2013')

2. For debug
addpath('/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/9069'); global VOC_CONFIG_OVERRIDE; VOC_CONFIG_OVERRIDE = @voc_config_9069;
train_model( 'query_9069', 1);

3. For running on grid: 
nmduc/train_models_tv2013.sh  (--> nmduc/train_dpm_models_job.sh)