import junit.framework.Test;
import junit.framework.TestCase;
import junit.framework.TestSuite;

import com.meterware.httpunit.Button;
import com.meterware.httpunit.GetMethodWebRequest;
import com.meterware.httpunit.SubmitButton;
import com.meterware.httpunit.WebConversation;
import com.meterware.httpunit.WebForm;
import com.meterware.httpunit.HTMLElement;
import com.meterware.httpunit.WebRequest;
import com.meterware.httpunit.WebResponse;

/**
 * Test class for Cartoweb HttpUnit.
 * 
 * @author sypasche
 */
public class CartowebTest extends TestCase {

    static String URL;
    
    public CartowebTest(String name) {
        super(name);
    }

    public static void main(String args[]) {        
        if (args.length != 1) {
            System.out.println("Usage: CartowebTest CARTOCLIENT_URL\n");
            System.exit(0);
        }
        CartowebTest.URL = args[0];

        junit.textui.TestRunner.run(suite());
    }
    
    public static Test suite() {
        if (URL == null)
            URL = System.getProperty("cartoclient_url");
        if (URL == null)
            throw new RuntimeException("Please invoke this test via main(), with URL argument");
        
        return new TestSuite(CartowebTest.class);
    }

    private String getProjectUrl(String projectId) {
                
        return URL + "?project=" + projectId;
    }
    
    private void assertContainsMainmap(WebResponse response) throws Exception {
        
        HTMLElement mainmap = response.getElementWithID("mapImageDiv");
        assertNotNull("No mainmap image on cartoclient page", mainmap);
    }
    
    private void testMainmapPresent(String projectId) throws Exception {
        
        WebConversation     conversation = new WebConversation();
        WebRequest request = new GetMethodWebRequest(getProjectUrl(projectId));
        WebResponse response = conversation.getResponse(request);
        
        assertContainsMainmap(response);
    }

    private void testKeymap(String projectId) throws Exception {
                            
        WebConversation     conversation = new WebConversation();
        WebResponse response = conversation.getResponse(getProjectUrl(projectId));
                
        assertContainsMainmap(response);
                
        // click on the center of the keymap
        SubmitButton keymapButton = response.getFormWithName("carto_form").getSubmitButton("keymap");
        keymapButton.click(50, 50);
        assertContainsMainmap(conversation.getCurrentPage());
    }
    
    private void testProject(String projectId) throws Exception {
        testMainmapPresent("default");
        testKeymap("default");
    }
    
    public void testDefaultProject() throws Exception {
        testProject("default");
    }

    public void testTestprojectProject() throws Exception {
        String projectId = "testproject";
        testProject(projectId);
                        
        WebConversation conversation = new WebConversation();
        WebResponse response = conversation.getResponse(getProjectUrl(projectId));
        assertTrue("Testproject title does not contain \"test_project\"", 
                   response.getElementWithID("banner").getText().indexOf("testproject") != -1);
                        
    }

}
