function conf = voc_config_9085()
	conf.pascal.year = '9085';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9085/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9085/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9085/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9085/Images/%s.txt';
end