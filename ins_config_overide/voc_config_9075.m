function conf = voc_config_9075()
	conf.pascal.year = '9075';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9075/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9075/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9075/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9075/Images/%s.txt';
end