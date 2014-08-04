function conf = voc_config_9088()
	conf.pascal.year = '9088';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9088/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9088/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9088/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9088/Images/%s.txt';
end