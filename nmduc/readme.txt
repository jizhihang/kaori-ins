HOW TO TRAIN DPM MODELS

1. Run prepare_annotation_for_dpm to generate training images (pos, neg) and annotations (bounding boxes) based on query images and masks.

my_VOCinit 
else % always  use this because conf.pascal.VOCopts.imgsetpath is set in voc_config_9069.m
    VOCopts.imgsetpath = [conf.pascal.VOCopts.imgsetpath]; % we use absolute path in voc_config_9069.m
end

/raid0/ledduy/github-projects/kaori-ins2014/voc-release5/star-cascade/data -->